<?php

namespace Dennisbusk\DebugNotary\Nova\Filters;

use Dennisbusk\DebugNotary\Enums\BugSeverity;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class BugSeverityFilter extends Filter {

    public $name = 'Severity';

    public $component = 'select-filter';

    public function apply( NovaRequest $request, Builder $query, mixed $value ): Builder {
        return $query->where('severity', $value);
    }

    public function options( NovaRequest $request ): array {
        $options = [];
        foreach ( BugSeverity::cases() as $severity ) {
            $options[ $severity->label() ] = $severity->value;
        }

        return $options;
    }
}
