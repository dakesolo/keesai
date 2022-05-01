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

use App\Controller\BehaviorController;
use App\Controller\TransactionController;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

Router::get('/favicon.ico', function () {
    return '';
});

Router::addGroup('/transaction', function () {
    Router::post('/submitTransaction', [TransactionController::class, 'submitTransaction']);
});

Router::addGroup('/behavior', function () {
    Router::post('/submitBehavior', [BehaviorController::class, 'submitBehavior']);
});

Router::addRoute(['GET', 'POST', 'HEAD'], '/submitOrder', 'App\Controller\IndexController@submitOrder');

Router::addRoute(['GET', 'POST', 'HEAD'], '/order/createOrder', 'App\Controller\Order@createOrder');
Router::addRoute(['GET', 'POST', 'HEAD'], '/order/createOrderCompensate', 'App\Controller\Order@createOrderCompensate');

Router::addRoute(['GET', 'POST', 'HEAD'], '/product/debitProduct', 'App\Controller\Product@debitProduct');
Router::addRoute(['GET', 'POST', 'HEAD'], '/product/debitProductCompensate', 'App\Controller\Product@debitProductCompensate');

Router::addRoute(['GET', 'POST', 'HEAD'], '/user/debitMoney', 'App\Controller\User@debitMoney');
Router::addRoute(['GET', 'POST', 'HEAD'], '/user/debitMoneyCompensate', 'App\Controller\User@debitMoneyCompensate');

Router::addRoute(['GET', 'POST', 'HEAD'], '/user/exchangeCouponMoney', 'App\Controller\User@exchangeCouponMoney');
Router::addRoute(['GET', 'POST', 'HEAD'], '/user/exchangeCouponCompensate', 'App\Controller\User@exchangeCouponCompensate');