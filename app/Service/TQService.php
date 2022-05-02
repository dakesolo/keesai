<?php

namespace App\Service;

use App\Job\TQJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\Logger\LoggerFactory;

class TQService
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var DriverInterface
     */
    protected $driver;

    public function __construct(DriverFactory $driverFactory, LoggerFactory $loggerFactory)
    {
        $this->driver = $driverFactory->get('TQ');
        $this->logger = $loggerFactory->get('log');
    }

    /**
     * 生产消息.
     * @param $params 数据
     * @param int $delay 延时时间 单位秒
     */
    public function push(array $params, int $delay = 0): bool
    {
        $this->logger->info(111);
        return $this->driver->push(new TQJob($params), $delay);
    }
}