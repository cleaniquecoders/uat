<?php

namespace CleaniqueCoders\Uat\Commands;

use Illuminate\Console\Command;

class UatCommand extends Command
{
    public $signature = 'uat';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
