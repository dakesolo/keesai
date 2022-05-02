<?php

namespace App\Job;

use App\Model\Bd;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\AsyncQueue\Job;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;

class CQJob extends Job
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
        // 以下补偿如果失败一次，需要手工处理
        $bd = Bd::query()
            ->where('id', $this->params['behaviorId'])
            ->first();
        if(!$bd) {
            return;
        }
        if($bd->status == 'failed' || $bd->status == 'pending') {
            return;
        }
        $bd->status = 'failed';
        $bd->save();
        // 开始补偿
        $client = ApplicationContext::getContainer()->get(ClientFactory::class)->create();
        try {
            $response = $client->request(...$this->params['compensate']);
            $content = json_decode($response->getBody()->getContents(), true);
            if (isset($content['error'])) {
                // 如果是补偿，则发送消息
                $bd->compensate_code = $content['error'];
                $bd->compensate_error_message = $content['message'];
                $bd->save();
                return;
            }

        } catch (GuzzleException $e) {
            $bd->compensate_code = 'error';
            $bd->compensate_error_message = $e->getMessage();
            $bd->save();
        }
    }
}