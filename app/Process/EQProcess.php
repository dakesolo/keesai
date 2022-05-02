<?php

namespace App\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process()
 */
class EQProcess extends ConsumerProcess
{
    /**
     * @var string
     */
    protected $queue = 'EQ';
}