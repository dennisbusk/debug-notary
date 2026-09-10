<?php

namespace Dennisbusk\DebugNotary\Nova\Metrics;

use Dennisbusk\DebugNotary\Models\RecordedBug;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class BugsTrendPerDay extends Trend
{
    public $name = 'Bugs Over Time';

    public function calculate(NovaRequest $request): TrendResult
    {
        return $this->countByDays($request, RecordedBug::class);
    }

    public function ranges(): array
    {
        return [
            7 => __('7 Days'),
            14 => __('14 Days'),
            30 => __('30 Days'),
            60 => __('60 Days'),
            90 => __('90 Days'),
        ];
    }
}
