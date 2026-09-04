<?php

namespace Dennisbusk\DebugNotary\Nova\Actions;

use Dennisbusk\DebugNotary\Enums\BugStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class MarkAsOpen extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Mark as Open';

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        foreach ($models as $model) {
            $model->update([
                'status' => BugStatus::OPEN->value,
            ]);
        }

        return ActionResponse::message(__('debug-notary::messages.status_updated'));
    }

    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
