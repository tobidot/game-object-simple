<?php

namespace App\Console\Commands;

use App\Services\StorageCleaner;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * The command-line way in to `StorageCleaner`; Nova has the same as two actions.
 */
class CleanStorage extends Command
{
    protected $signature = 'app:clean-storage {--delete : Delete for real; without it the command only reports}';

    protected $description = 'Remove stored files that no page or package references';

    public function handle(StorageCleaner $cleaner): int
    {
        try {
            $plan = $cleaner->plan();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Unreferenced', 'Files', 'MB'],
            array_map(
                fn (string $root, array $total): array => [$root, $total['files'], round($total['bytes'] / 1048576, 1)],
                array_keys($plan->by_root),
                $plan->by_root,
            ),
        );
        $this->line($plan->summary());

        if (! $this->option('delete')) {
            $this->info('Nothing was deleted. Run again with --delete to remove the above.');

            return self::SUCCESS;
        }

        $cleaner->execute($plan);
        $this->info('Deleted.');

        return self::SUCCESS;
    }
}
