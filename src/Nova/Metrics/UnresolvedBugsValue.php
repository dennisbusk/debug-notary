<?php

namespace Dennisbusk\DebugNotary\Nova\Metrics;

use Dennisbusk\DebugNotary\Enums\BugStatus;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class UnresolvedBugsValue extends Value {

    public $name = 'Unresolved Bugs';

    public function calculate( NovaRequest $request ): ValueResult {
        return $this->count(
            $request,
            RecordedBug::whereNotIn('status', [ BugStatus::RESOLVED->value, BugStatus::WONT_FIX->value ])
        );
    }

    public function ranges(): array {
        return [
            30      => __('30 Days'),
            60      => __('60 Days'),
            90      => __('90 Days'),
            'TODAY' => __('Today'),
            'MTD'   => __('Month To Date'),
            'QTD'   => __('Quarter To Date'),
            'YTD'   => __('Year To Date'),
        ];
    }
}
