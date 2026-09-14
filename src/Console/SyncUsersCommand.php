<?php

namespace Dennisbusk\DebugNotary\Console;

use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Illuminate\Console\Command;

class SyncUsersCommand extends Command
{
    protected $signature = 'debug-notary:sync-users {--dry-run : List users that would be synced without sending}';

    protected $description = 'Synchronize local users to DebugCentral server';

    public function handle(): int
    {
        $this->info('🚀 Starting Debug Notary User Sync...');

        if (! config('debug-notary.central.enabled')) {
            $this->error('❌ DebugCentral integration is disabled in config (debug-notary.central.enabled).');

            return 1;
        }

        $url = config('debug-notary.central.api_url');
        $apiKey = config('debug-notary.central.api_key');

        if (! $url || ! $apiKey) {
            $this->error('❌ DebugCentral API URL or API Key is missing in config.');

            return 1;
        }

        $users = DebugNotary::resolveUsersForSync();

        $this->info('📋 Found '.count($users).' user(s) to sync:');

        $this->table(
            ['ID', 'Name', 'Email', 'Role'],
            array_map(fn ($u) => [$u['id'] ?? '', $u['name'] ?? '', $u['email'] ?? '', $u['role'] ?? '—'], $users)
        );

        if ($this->option('dry-run')) {
            $this->warn('🔍 Dry-run mode: Users were not sent to DebugCentral.');

            return 0;
        }

        try {
            $this->comment('Sending users to DebugCentral at '.$url.'...');
            $count = DebugNotary::syncUsersToCentral($users);
            $this->info("✅ Successfully synced {$count} user(s) to DebugCentral.");

            return 0;
        } catch (\Throwable $e) {
            $this->error('❌ Failed to sync users: '.$e->getMessage());

            return 1;
        }
    }
}
