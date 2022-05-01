<?php

namespace App\Controller;

use App\Log;
use Ramsey\Uuid\Uuid;

class Coupon extends AbstractController
{
    public function exchangeCouponMoney(): array
    {
        $transactionId = $this->request->input('transactionId');
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return [];
    }

    public function exchangeCouponMoneyCompensate(): array
    {
        $transactionId = $this->request->input('transactionId');
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return [];
    }
}