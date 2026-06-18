<?php

namespace Dennisbusk\DebugNotary\Enums;

enum BugSeverity: string {

    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function weight(): int {
        return match ( $this ) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }

    public function label(): string {
        return __('debug-notary::messages.severity_' . $this->value);
    }

    public function color(): string {
        return match ( $this ) {
            self::LOW => 'blue',
            self::MEDIUM => 'yellow',
            self::HIGH => 'orange',
            self::CRITICAL => 'red',
        };
    }
}
