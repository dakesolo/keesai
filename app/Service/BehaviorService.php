<?php

namespace App\Service;

use App\Job\BQJob;
use App\Log;
use App\Model\Bd;
use App\Model\Td;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class BehaviorService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->driver = $driverFactory->get('BQ');
        $this->logger = Log::get(get_class());
    }

    public function createBehavior($bdList): bool
    {
        $save = [];
        foreach ($bdList as $k=>$bd) {
            if(Bd::query()->where('transaction_id', $bd['transactionId'])->exists()) break;
            $save[$k]['id'] = Uuid::uuid4()->toString();
            $save[$k]['transaction_id'] = $bd['transactionId'];
            $save[$k]['name'] = $bd['name'];
            $save[$k]['consistency'] = $bd['consistency'];
            $save[$k]['status'] = 'pending';
            $save[$k]['execute'] = json_encode($bd['execute'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $save[$k]['compensate'] = json_encode($bd['compensate'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $save[$k]['error_code'] = '';
            $save[$k]['error_message'] = '';
            $save[$k]['created_at'] = date('y-m-d H:i:s', time());;
            $save[$k]['updated_at'] = date('y-m-d H:i:s', time());;
            $save[$k]['retry'] = $bd['retry'];
            $save[$k]['retry_max'] = $bd['retry_max'];
        }
        Bd::insert($save);
        foreach ($save as $k=>$item) {
            $item['compensate'] = $bdList[$k]['compensate'];
            $item['execute'] = $bdList[$k]['execute'];
            $params = Bd::createMessage($item);
            $this->driver->push(new BQJob($params));
        }
        return true;
    }
}