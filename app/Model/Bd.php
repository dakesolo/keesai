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
namespace App\Model;

 class Bd extends Model
{
     protected $table = 'bd';
     protected $casts = [
         'execute' => 'array',
         'compensate' => 'array'
     ];
     // protected $fillable = ['name', 'id', 'transaction_id', 'created_at', 'updated_at', 'status', 'consistency', 'error_code', 'error_message', 'retry', 'retry_max', 'execute', 'compensate'];
     /*protected $fillable = [
         'options->enabled',
     ];*/
     protected $guarded = [];


     public static function createMessage($item): array
     {
         $params = [];
         $params['behaviorId'] = $item['id'];
         $params['transactionId'] = $item['transaction_id'];
         $params['consistency'] = $item['consistency'];
         $params['name'] = $item['name'];
         $params['status'] = $item['status'];
         $params['compensate'] = $item['compensate'];
         $params['execute'] = $item['execute'];
         $params['retry'] = $item['retry'];
         $params['retry_max'] = $item['retry_max'];
         return $params;
     }
}
