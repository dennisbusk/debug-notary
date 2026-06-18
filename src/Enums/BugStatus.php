<?php

namespace Dennisbusk\DebugNotary\Enums;

enum BugStatus: string {

    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case PENDING = 'pending';
    case RESOLVED = 'resolved';
    case WONT_FIX = 'wont_fix';

    public function label(): string {
        return __('debug-notary::messages.status_' . $this->value);
    }

    public function color(): string {
        return match ( $this ) {
            self::OPEN => 'red',
            self::IN_PROGRESS => 'blue',
            self::PENDING => 'yellow',
            self::RESOLVED => 'green',
            self::WONT_FIX => 'gray',
        };
    }
}
