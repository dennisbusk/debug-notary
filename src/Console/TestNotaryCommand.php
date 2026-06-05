<?php

namespace Dennisbusk\DebugNotary\Console;

use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Illuminate\Console\Command;

class TestNotaryCommand extends Command
{
    protected $signature = 'debug-notary:test';

    protected $description = 'Send a test bug report to verify Debug Notary configuration';

    public function handle(): int
    {
        $this->info('🚀 Starting Debug Notary Test...');

        if (! config('debug-notary.enabled')) {
            $this->error('❌ Debug Notary is disabled in config. Please enable it to run the test.');

            return 1;
        }

        $this->comment('Recording a dummy error...');

        // Vi logger en manuel fejl med en unik besked så vi kan se den i dashboardet
        $testMessage = 'Debug Notary Test Error - '.now()->toDateTimeString();

        DebugNotary::error($testMessage, [
            'test_mode' => true,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);

        $this->info('✅ Test bug recorded in database.');

        if (config('debug-notary.notifications.enabled')) {
            $this->info('🔔 Notifications are enabled. Checking channels...');

            if (config('debug-notary.notifications.slack_webhook')) {
                $this->line('- Slack: Attempting to send...');
            }

            if (config('debug-notary.notifications.mail_to')) {
                $this->line('- Email: Attempting to send to '.config('debug-notary.notifications.mail_to'));
            }

            $this->comment('Note: If queuing is enabled, check your worker/queue logs.');
        } else {
            $this->warn('⚠️ Notifications are disabled in config. No notifications were sent.');
        }

        $this->info('🏁 Test completed! Please check your dashboard to confirm.');

        return 0;
    }
}
