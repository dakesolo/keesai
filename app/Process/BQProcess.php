<?php

namespace App\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process()
 */
class BQProcess extends ConsumerProcess
{
    /**
     * @var string
     */
    protected $queue = 'BQ';
}