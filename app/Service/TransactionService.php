<?php

namespace App\Service;

use App\Job\EQJob;
use App\Log;
use App\Model\Td;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class TransactionService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DriverInterface
     */
    protected $driver;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->driver = $driverFactory->get('EQ');
        $this->logger = Log::get(get_class());
    }

    public function createTransaction($td): string
    {
        $id = Uuid::uuid4()->toString();
        $model = new Td();
        $model->id = $id;
        $model->name = $td['name'];
        $model->status = 'pending';
        $model->expire = $td['expire'];
        $model->save();

        // 设置延时消息，如果TD依然pending，则设置为failed；如果不是，则忽略
        $this->driver->push(new EQJob([
            'transactionId' => $id
        ]), $td['expire']);
        return $id;
    }
}