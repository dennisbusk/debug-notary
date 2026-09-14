<?php

namespace Dennisbusk\DebugNotary\Nova\Actions;

use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;

class SetEstimate extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Set Estimate';

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $hours = $fields->estimate_hours !== null && $fields->estimate_hours !== '' ? (int) $fields->estimate_hours : null;
        $minutes = $fields->estimate_minutes !== null && $fields->estimate_minutes !== '' ? (int) $fields->estimate_minutes : null;

        if ($hours === 0 && $minutes === 0) {
            $hours = null;
            $minutes = null;
        }

        $user = auth()->user();
        $userName = $user?->name ?? 'System';

        foreach ($models as $model) {
            if ($model->isEstimateAccepted()) {
                return ActionResponse::danger(__('debug-notary::messages.estimate_locked_error'));
            }

            $model->update([
                'estimate_hours' => $hours,
                'estimate_minutes' => $minutes,
            ]);

            $model->refresh();
            DebugNotary::syncBugUpdateToCentral($model);

            $formatted = $model->formattedEstimate();
            if ($formatted) {
                $model->messages()->create([
                    'user_id' => $user?->id,
                    'message' => __('debug-notary::messages.history_estimate_set', [
                        'estimate' => $formatted,
                        'user' => $userName,
                    ]),
                ]);
            } else {
                $model->messages()->create([
                    'user_id' => $user?->id,
                    'message' => __('debug-notary::messages.history_estimate_removed', [
                        'user' => $userName,
                    ]),
                ]);
            }
        }

        return ActionResponse::message(__('debug-notary::messages.status_updated'));
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Number::make(__('debug-notary::messages.hours'), 'estimate_hours')
                ->min(0)
                ->step(1)
                ->placeholder('0'),

            Number::make(__('debug-notary::messages.minutes'), 'estimate_minutes')
                ->min(0)
                ->max(59)
                ->step(1)
                ->placeholder('0'),
        ];
    }
}
