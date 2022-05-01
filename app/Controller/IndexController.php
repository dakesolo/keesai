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
use App\Service\TQService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;
use Ramsey\Uuid\Uuid;

class IndexController extends AbstractController
{
    private $rootUrl = 'http://192.168.175.128:9501/';
    /**
     * @Inject
     * @var TQService
     */
    protected $tQService;

    /**
     * @Inject
     * @var BQService
     */
    protected $bQService;

    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();
        $this->tQService->push([
            'this tq message'
        ]);
        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    public function submitTransaction() {
        /*$TD = [
            'transactionId' => Uuid::uuid4()->toString(),
            'name' => __FUNCTION__,
            'behaviors' => [
                'createOrder',
                'debitMoney',
                'debitProduct',
                'exchangeCoupon'
            ],
            'expire' => 30000,
            'created_at' => $date
        ];*/
    }

    public function submitOrder(): array
    {
        $productId = 1;
        $couponId = 12;
        $userId = 35;
        $amount = 50;
        $date = date('y-m-d H:i:s', time());

        // 提交事务清单
        $TD = [
            'transactionId' => Uuid::uuid4()->toString(),
            'name' => __FUNCTION__,
            'behaviors' => [
                'createOrder',
                'debitMoney',
                'debitProduct',
                'exchangeCoupon'
            ],
            'expire' => 10
        ];
        $client = ApplicationContext::getContainer()->get(ClientFactory::class)->create();
        $response = $client->post($this->rootUrl.'transaction/submitTransaction', [
            'json'=>$TD
        ]);
        $package = json_decode($response->getBody()->getContents(), true);
        if(isset($package['error'])) {
            return $package;
        }
        $transactionId = $package['transactionId'];


        // 提交 debitProduct 行为清单
        $BD = [
            'version' => '1.0.1',
            'transactionId' => $transactionId,
            'name' => 'debitProduct',
            'consistency' => 'compensate',
            'status' => 'pending',
            'action' => [
                [
                    'name' => 'debitProduct',
                    'execute' => [
                        'GET',
                        $this->rootUrl . 'product/debitProduct',
                        [
                            'query' => [
                                'productId'=>$productId
                            ]
                        ]
                    ],
                    'compensate' => [
                        'GET',
                        $this->rootUrl . 'product/debitProductCompensate',
                        [
                            'query' => [
                                'productId'=>$productId,
                                'transactionId' => $transactionId
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->bQService->push($BD);
        return [
            'transactionId' => $transactionId,
        ];
    }
}
