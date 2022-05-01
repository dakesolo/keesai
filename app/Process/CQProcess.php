<?php

namespace App\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process()
 */
class CQProcess extends ConsumerProcess
{
    /**
     * @var string
     */
    protected $queue = 'CQ';
}