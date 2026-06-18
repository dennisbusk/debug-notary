<?php

namespace Dennisbusk\DebugNotary\Mail;

use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BugActivityMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecordedBug $bug,
        public string $activityType,
        public array $data = []
    ) {}

    public function build()
    {
        $subject = match ($this->activityType) {
            'new_message' => 'New message on bug #'.$this->bug->id,
            'assigned' => 'You have been assigned to bug #'.$this->bug->id,
            default => 'Activity on bug #'.$this->bug->id,
        };

        return $this->subject($subject)
            ->markdown('debug-notary::mail.bug-activity');
    }
}
