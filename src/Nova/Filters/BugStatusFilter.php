<?php

namespace Dennisbusk\DebugNotary\Nova\Filters;

use Dennisbusk\DebugNotary\Enums\BugStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class BugStatusFilter extends Filter
{
    public $name = 'Status';

    public $component = 'select-filter';

    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        return $query->where('status', $value);
    }

    public function options(NovaRequest $request): array
    {
        $options = [];
        foreach (BugStatus::cases() as $status) {
            $options[$status->label()] = $status->value;
        }

        return $options;
    }
}
