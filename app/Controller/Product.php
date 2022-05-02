<?php

namespace App\Controller;

use App\Log;
use Ramsey\Uuid\Uuid;

class Product extends AbstractController
{
    public function debitProduct(): array
    {
        $transactionId = $this->request->input('transactionId');
        $productId = $this->request->input('productId');
        if($productId == 2) {
            Log::get()->info($transactionId, [
                __FUNCTION__.' failing'
            ]);
            return [
                'error'=>'failing',
                'message'=>'product lack'
            ];
        }
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return [
            'productId'=>Uuid::uuid4()->toString()
        ];
    }

    public function debitProductCompensate(): string
    {
        $transactionId = $this->request->input('transactionId');
        Log::get()->info($transactionId, [
            __FUNCTION__.' success'
        ]);
        return $this->response->raw('');
    }
}