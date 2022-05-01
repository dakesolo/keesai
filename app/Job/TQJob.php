<?php

namespace App\Job;

use App\Log;
use App\Model\Bd;
use App\Model\Td;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Job;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class TQJob extends Job
{
    public $params;

    /**
     * 任务执行失败后的重试次数，即最大执行次数为 $maxAttempts+1 次
     *
     * @var int
     */
    protected $maxAttempts = 2;

    public function __construct($params)
    {
        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->params = $params;
    }

    public function handle()
    {
        // 以下处理保证状态流向为单项，并且不存在failed->success的情况发生
        $this->{$this->params['status']}();
    }

    public function failing() {
        Td::query()
            ->where('id', $this->params['transactionId'])
            ->update([
            'status'=>'failed'
        ]);
        $this->compensate();
    }

    public function success() {
        $td = Td::query()
            ->where('id', $this->params['transactionId'])
            ->first();
        // 如果当前transaction失败，则需要补偿
        if($td->status == 'failed') {
            $this->compensate();
            return;
        }

        // 查看所有behavior状态
        $bdList = Bd::query()->where('transaction_id', $this->params['transactionId'])->get();
        $allSuccess = true;
        foreach ($bdList as $bd) {
            // 如果有一个不成功，则返回
            if($bd->status != 'success') {
                $allSuccess = false;
                break;
            }
        }

        // 如果全部success，则设置全局成功
        if($allSuccess) {
            Td::query()
                ->where('id', $this->params['transactionId'])
                ->where('status', 'pending')
                ->update([
                    'status'=>'success'
                ]);
        }
    }

    /**
     * 全部补偿
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function compensate(): void
    {
        $bdList = Bd::query()->where('transaction_id', $this->params['transactionId'])->get();
        foreach ($bdList as $bd) {
            $params = Bd::createMessage($bd->toArray());
            ApplicationContext::getContainer()->get(DriverFactory::class)->get('CQ')->push(new CQJob($params));
        }
    }
}