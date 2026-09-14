<?php

namespace Dennisbusk\DebugNotary\Nova\Actions;

use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class AcceptEstimate extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Accept Estimate';

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $user = auth()->user();
        $userName = $user?->name ?? 'Ukendt';
        $acceptedAt = now();
        $count = 0;

        foreach ($models as $model) {
            if ($model->isEstimateAccepted()) {
                continue;
            }

            if ($model->estimate_hours === null && $model->estimate_minutes === null) {
                return ActionResponse::danger(__('debug-notary::messages.estimate_not_set'));
            }

            $model->update([
                'estimate_accepted_at' => $acceptedAt,
                'estimate_accepted_by_id' => $user?->id,
                'estimate_accepted_by_name' => $userName,
            ]);

            $model->refresh();
            DebugNotary::syncBugUpdateToCentral($model);

            $formatted = $model->formattedEstimate() ?: '0 timer 0 minutter';
            $dateStr = $acceptedAt->format('d/m/Y H:i');

            $model->messages()->create([
                'user_id' => $user?->id,
                'message' => __('debug-notary::messages.history_estimate_accepted', [
                    'estimate' => $formatted,
                    'user' => $userName,
                    'time' => $dateStr,
                ]),
            ]);

            $count++;
        }

        if ($count === 0 && $models->count() > 0) {
            return ActionResponse::danger(__('debug-notary::messages.estimate_accepted'));
        }

        return ActionResponse::message(__('debug-notary::messages.estimate_accepted'));
    }

    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
