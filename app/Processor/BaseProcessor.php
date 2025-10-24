<?php

namespace App\Processor;

use Carbon\Carbon;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

abstract class BaseProcessor
{
    protected Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        if (! $logger) {
            $logger = new Logger('smart-dato-hub');
            $logger->pushHandler(new StreamHandler(storage_path('/logs/'.Carbon::now()->format('Y-m-d').'-smart-dato-hub.log'), Level::Debug));
        }

        $this->logger = $logger;
    }
}
