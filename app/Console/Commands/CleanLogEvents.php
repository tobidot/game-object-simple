<?php

namespace App\Console\Commands;

use App\Models\LogEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanLogEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-log-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean the old logs in the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() : int
    {
        $log_events = LogEvent::query()
            ->whereDate('created_at', '<', now()->subMonths(3))
        ;
        $count = $log_events->count();
        $log_events->delete();
        Log::info("Cleaned $count Log Events");
        return self::SUCCESS;
    }
}
