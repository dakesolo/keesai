<?php

namespace App\Controller;

use App\Log;
use Ramsey\Uuid\Uuid;

class Order extends AbstractController
{
    public function createOrder(): array
    {
        $transactionId = $this->request->input('transactionId');
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return [
            'orderId'=>Uuid::uuid4()->toString()
        ];
    }

    public function createOrderCompensate(): string
    {
        $transactionId = $this->request->input('transactionId');
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return $this->response->raw('');
    }
}