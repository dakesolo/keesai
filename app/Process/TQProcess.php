<?php

namespace App\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process()
 */
class TQProcess extends ConsumerProcess
{
    /**
     * @var string
     */
    protected $queue = 'TQ';
}