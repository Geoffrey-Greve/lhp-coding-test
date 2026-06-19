<?php

namespace App\Console\Commands;

use App\Services\EventImageAssigner;
use Illuminate\Console\Command;

class AssignEventImages extends Command
{
    protected $signature = 'events:assign-images {--chunk=2000 : Rows per chunk}';

    protected $description = 'Assign local placeholder images to events missing them';

    public function handle(EventImageAssigner $assigner): int
    {
        $chunk = (int) $this->option('chunk');
        $count = $assigner->assignMissing($chunk);
        $this->info("Assigned images to {$count} events.");

        return self::SUCCESS;
    }
}
