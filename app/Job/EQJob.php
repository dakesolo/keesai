<?php

namespace App\Job;

use App\Log;
use App\Model\Td;
use Hyperf\AsyncQueue\Job;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class EQJob extends Job
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
        Td::query()
            ->where('id', $this->params['transactionId'])
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'desc'=>'expired'
            ]);
        Log::get()->info('message', $this->params);
    }
}