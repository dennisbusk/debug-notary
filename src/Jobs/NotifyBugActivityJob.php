<?php

namespace Dennisbusk\DebugNotary\Jobs;

use Dennisbusk\DebugNotary\Mail\BugActivityMail;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyBugActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected RecordedBug $bug,
        protected string $activityType,
        protected array $data = []
    ) {}

    public function handle(): void
    {
        if (! config('debug-notary.notifications.enabled') || ! config('debug-notary.notifications.mail_enabled')) {
            return;
        }

        $recipients = [];

        if ($this->activityType === 'assigned') {
            if ($this->bug->assignedTo && $this->bug->assignedTo->email) {
                $recipients[] = $this->bug->assignedTo->email;
            }
        } elseif ($this->activityType === 'new_message') {
            // Send til den tildelte bruger, hvis afsenderen ikke er dem selv
            if ($this->bug->assignedTo && $this->bug->assignedTo->email && $this->bug->assignedTo->id !== auth()->id()) {
                $recipients[] = $this->bug->assignedTo->email;
            } else {
                // Send til standard mail_to hvis ingen er tildelt eller afsenderen er den tildelte
                if ($email = config('debug-notary.notifications.mail_to')) {
                    $recipients[] = $email;
                }
            }
        }

        foreach (array_unique($recipients) as $recipient) {
            try {
                Mail::to($recipient)->send(new BugActivityMail($this->bug, $this->activityType, $this->data));
            } catch (\Exception $e) {
                // Silent fail
            }
        }
    }
}
