<?php

namespace App\Controller;

use App\Log;
use Ramsey\Uuid\Uuid;

class User extends AbstractController
{
    public function debitMoney(): array
    {
        $transactionId = $this->request->input('transactionId');
        $amount = $this->request->input('amount');
        if($amount > 100) {
            Log::get()->info($transactionId, [
                __FUNCTION__.' failing'
            ]);
            return [
                'error'=>'failing',
                'message'=>'user amount lack'
            ];
        }
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return [];
    }

    public function debitMoneyCompensate(): string
    {
        $transactionId = $this->request->input('transactionId');
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return $this->response->raw('');
    }
}