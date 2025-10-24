<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Playground extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:playground';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Playground';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // @todo sync categories
        // @todo sync manufacturers add table

        // @todo maybe difference between detailed response and normal response, check in manufacturer

        // @todo sync languages
        // @todo sync products
        // @todo sync properties
        // @todo sync media

        // @todo sync countries // no countries in pim
        // @todo sync currencies // currency has no Currency exchange rate, also other fields missing

        return self::SUCCESS;
    }
}
