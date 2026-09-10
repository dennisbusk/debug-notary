<?php

namespace Dennisbusk\DebugNotary\Nova\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class BugEstimateFilter extends Filter
{
    public $component = 'select-filter';

    public $name = 'Estimate Status';

    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        return match ($value) {
            'accepted' => $query->whereNotNull('estimate_accepted_at'),
            'pending' => $query->whereNull('estimate_accepted_at')
                ->where(function ($q) {
                    $q->whereNotNull('estimate_hours')
                        ->orWhereNotNull('estimate_minutes');
                }),
            'not_set' => $query->whereNull('estimate_hours')
                ->whereNull('estimate_minutes'),
            default => $query,
        };
    }

    public function options(NovaRequest $request): array
    {
        return [
            __('debug-notary::messages.estimate_accepted') => 'accepted',
            __('debug-notary::messages.estimate_pending') => 'pending',
            __('debug-notary::messages.estimate_not_set') => 'not_set',
        ];
    }
}
