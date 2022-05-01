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


use App\Service\BehaviorService;
use Hyperf\Di\Annotation\Inject;

class BehaviorController extends AbstractController
{
    /**
     * @Inject
     * @var BehaviorService
     */
    protected $behavioService;

    public function submitBehavior(): \Psr\Http\Message\ResponseInterface
    {
        $bd = $this->request->post();
        $this->behavioService->createBehavior($bd);
        return $this->response->raw('');
    }
}
