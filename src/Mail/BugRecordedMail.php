<?php

namespace Dennisbusk\DebugNotary\Mail;

use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BugRecordedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RecordedBug $bug) {}

    public function build()
    {
        return $this->subject('New Debug Notary Bug')
            ->markdown('debug-notary::mail.bug-recorded');
    }
}
