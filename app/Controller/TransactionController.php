<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use App\Service\BQService;

use App\Service\TransactionService;
use Hyperf\Di\Annotation\Inject;

class TransactionController extends AbstractController
{
    /**
     * @Inject
     * @var TransactionService
     */
    protected $transactionService;

    public function submitTransaction() {
        $td = $this->request->post();
        $transactionId = $this->transactionService->createTransaction($td);
        return [
            'transactionId'=>$transactionId
        ];
    }
}
