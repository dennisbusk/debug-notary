<?php

namespace Dennisbusk\DebugNotary\Nova\Metrics;

use Dennisbusk\DebugNotary\Enums\BugSeverity;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class BugsBySeverityPartition extends Partition {

    public $name = 'Bugs by Severity';

    public function calculate( NovaRequest $request ): PartitionResult {
        return $this->count($request, RecordedBug::class, 'severity')
                    ->label(function ( $value ) {
                        $severity = BugSeverity::tryFrom((string) $value);

                        return $severity ? $severity->label() : ucfirst((string) $value);
                    })
                    ->colors([
                        'low'      => '#60a5fa',
                        'medium'   => '#facc15',
                        'high'     => '#fb923c',
                        'critical' => '#f87171',
                    ]);
    }
}
