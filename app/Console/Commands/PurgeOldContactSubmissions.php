<?php

namespace App\Console\Commands;

use App\Models\ContactRetentionRule;
use App\Models\ContactSubmission;
use Illuminate\Console\Command;

class PurgeOldContactSubmissions extends Command
{
    protected $signature = 'contact:purge-old-submissions
                            {--webform= : Only purge submissions for this specific webform_id}
                            {--days= : Override retention days (ignores stored rules)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete contact submissions older than the configured retention period';

    public function handle(): int
    {
        $dryRun     = $this->option('dry-run');
        $overrideWebform = $this->option('webform');
        $overrideDays    = $this->option('days');

        if ($dryRun) {
            $this->warn('[DRY RUN] No records will be deleted.');
        }

        // Build list of (webform_id, days) pairs to process
        $jobs = $this->buildJobs($overrideWebform, $overrideDays);

        if (empty($jobs)) {
            $this->info('No retention rules configured. Nothing to do.');
            return Command::SUCCESS;
        }

        $totalDeleted = 0;

        foreach ($jobs as [$webformId, $days]) {
            if ($days === null) {
                $this->line("  webform_id={$webformId}: no limit (keep forever), skipping.");
                continue;
            }

            $cutoff = now()->subDays($days);

            $query = ContactSubmission::where('webform_id', $webformId)
                ->where('created_at', '<', $cutoff);

            $count = $query->count();

            if ($dryRun) {
                $this->line("  [DRY RUN] webform_id={$webformId}: would delete {$count} submission(s) older than {$days} day(s) (before {$cutoff->toDateString()}).");
            } else {
                $query->delete();
                $this->line("  webform_id={$webformId}: deleted {$count} submission(s) older than {$days} day(s).");
                $totalDeleted += $count;
            }
        }

        if (!$dryRun) {
            $this->info("Purge complete. Total deleted: {$totalDeleted}.");
        }

        return Command::SUCCESS;
    }

    /**
     * Build the list of [webform_id, retention_days] pairs to process.
     */
    private function buildJobs(?string $overrideWebform, mixed $overrideDays): array
    {
        $days = $overrideDays !== null ? (int) $overrideDays : null;

        if ($overrideWebform !== null) {
            $effectiveDays = $days ?? ContactRetentionRule::effectiveDaysFor($overrideWebform);
            return [[$overrideWebform, $effectiveDays]];
        }

        $jobs = [];

        // Collect all distinct webform_ids from submissions
        $allWebformIds = ContactSubmission::select('webform_id')
            ->distinct()
            ->pluck('webform_id')
            ->filter()
            ->all();

        foreach ($allWebformIds as $webformId) {
            $effectiveDays = $days ?? ContactRetentionRule::effectiveDaysFor($webformId);
            if ($effectiveDays !== null) {
                $jobs[] = [$webformId, $effectiveDays];
            }
        }

        return $jobs;
    }
}
