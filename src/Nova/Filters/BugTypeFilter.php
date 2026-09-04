<?php

namespace Dennisbusk\DebugNotary\Nova\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class BugTypeFilter extends Filter {

    public $name = 'Type';

    public $component = 'select-filter';

    public function apply( NovaRequest $request, Builder $query, mixed $value ): Builder {
        return $query->where('log_type', $value);
    }

    public function options( NovaRequest $request ): array {
        return [
            'System'          => 'system',
            'Notary / Manuel' => 'manual',
            'JavaScript'      => 'javascript',
        ];
    }
}
