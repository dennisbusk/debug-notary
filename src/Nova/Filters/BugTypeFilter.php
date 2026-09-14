<?php

namespace Dennisbusk\DebugNotary\Nova\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class BugTypeFilter extends Filter
{
    public $name = 'Type';

    public $component = 'select-filter';

    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        if ($value === 'manual_all') {
            return $query->whereIn('log_type', ['manual', 'notary']);
        }

        return $query->where('log_type', $value);
    }

    public function options(NovaRequest $request): array
    {
        return [
            'System' => 'system',
            'Notary / Manuel' => 'manual_all',
            'Notary' => 'notary',
            'Manuel' => 'manual',
            'JavaScript' => 'javascript',
        ];
    }
}
