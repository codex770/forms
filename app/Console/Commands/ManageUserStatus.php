<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ManageUserStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:status {email} {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate or deactivate a user by email. Actions: activate, deactivate';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $action = $this->argument('action');

        if (!in_array($action, ['activate', 'deactivate'])) {
            $this->error('Action must be either "activate" or "deactivate"');
            return 1;
        }

        // Find user including soft deleted ones
        $user = User::withTrashed()->where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if ($action === 'deactivate') {
            if ($user->deleted_at) {
                $this->warn("User '{$email}' is already deactivated.");
                return 0;
            }
            
            $user->delete();
            $this->info("✅ User '{$email}' has been deactivated.");
            
        } else { // activate
            if (!$user->deleted_at) {
                $this->warn("User '{$email}' is already active.");
                return 0;
            }
            
            $user->restore();
            $this->info("✅ User '{$email}' has been activated.");
        }

        return 0;
    }
}
