<?php

namespace Dennisbusk\DebugNotary\Jobs;

use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotifyBugJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected RecordedBug $bug) {}

    public function handle(): void
    {
        $message = "New Bug Recorded: {$this->bug->message} in {$this->bug->file}:{$this->bug->line}";

        // Slack
        if ($webhook = config('debug-notary.notifications.slack_webhook')) {
            try {
                Http::post($webhook, [
                    'text' => $message,
                ]);
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        // Mail
        if ($email = config('debug-notary.notifications.mail_to')) {
            try {
                Mail::raw($message, function ($m) use ($email) {
                    $m->to($email)->subject('New Debug Notary Bug');
                });
            } catch (\Exception $e) {
                // Silent fail
            }
        }
    }
}
