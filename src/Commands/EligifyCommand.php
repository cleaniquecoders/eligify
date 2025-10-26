<?php

namespace CleaniqueCoders\Eligify\Commands;

use Illuminate\Console\Command;

class EligifyCommand extends Command
{
    public $signature = 'eligify';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
