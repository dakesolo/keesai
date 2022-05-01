<?php

namespace App\Job;

use App\Log;
use App\Model\Bd;
use App\Model\Td;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\Context;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class BQJob extends Job
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
        // 根据参数处理具体逻辑
        // 通过具体参数获取模型等
        // 这里的逻辑会在 ConsumerProcess 进程中执行
        $this->{$this->params['status']}();
    }

    // 执行函数
    public function pending()
    {
        $client = ApplicationContext::getContainer()->get(ClientFactory::class)->create();
        $query = Bd::query()
            ->where('id', $this->params['behaviorId']);
        try {
            $response = $client->request(...$this->params['execute']);
            $content = json_decode($response->getBody()->getContents(), true);
            if (isset($content['error'])) {
                // 如果是补偿，则发送消息
                if ($this->params['consistency'] == 'compensate') {
                    // 改变数据库BD状态为failing,
                    $this->params['status'] = 'failing';
                    $query->where('status', 'pending')
                        ->update([
                            'status' => $this->params['status'],
                            'error_code' => $content['error'],
                            'error_message' => $content['message'] ?? ''
                        ]);
                    // 向TQ发个消息，说明该行为失败
                    ApplicationContext::getContainer()->get(DriverFactory::class)->get('TQ')->push(new TQJob($this->params));
                }

                // 如果是重试，则延时重试
                else {
                    $this->params['retry_times'] = isset($this->params['retry_times']) ? $this->params['retry_times'] + 1 : 0;
                    if ($this->params['retry_times'] < $this->params['retry_max']) {
                        ApplicationContext::getContainer()->get(DriverFactory::class)->get('BQ')->push(new BQJob($this->params), $this->params['retry']);
                    } else {
                        // 重试次数用完了，则改变数据库BD状态为failing,
                        $query->where('status', 'pending')
                            ->update([
                                'status' => 'failing',
                                'error_code' => $content['error'],
                                'error_message' => $content['message'] ?? ''
                            ]);
                    }
                }
                return;
            }

            // 成功执行
            $this->params['status'] = 'success';
            $query->where('status', 'pending')
                ->update([
                    'status' => $this->params['status']
                ]);

            // 向TQ发个消息，说明该行为成功
            ApplicationContext::getContainer()->get(DriverFactory::class)->get('TQ')->push(new TQJob($this->params));
        } catch (GuzzleException $e) {
            // 改变数据库BD状态为failing,
            $this->params['status'] = 'failing';
            $query->where('status', 'pending')
                ->update([
                    'status' => $this->params['status'],
                    'error_code' => 'error',
                    'error_message' => $e->getMessage()
                ]);

            // 向TQ发个消息，说明该行为失败
            ApplicationContext::getContainer()->get(DriverFactory::class)->get('TQ')->push(new TQJob($this->params));
            Log::get(get_class())->error($e->getMessage());
        }
    }

    /*// 开始补偿
    public function failing() {
        $client = ApplicationContext::getContainer()->get(ClientFactory::class)->create();
        Log::get(get_class())->info('开始补偿');
    }*/
}