<?php

namespace Dennisbusk\DebugNotary\Nova\Resources;

use Dennisbusk\DebugNotary\Enums\BugSeverity;
use Dennisbusk\DebugNotary\Enums\BugStatus;
use Dennisbusk\DebugNotary\Models\RecordedBug as RecordedBugModel;
use Dennisbusk\DebugNotary\Nova\Actions\AcceptEstimate;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsInProgress;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsOpen;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsResolved;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsWontFix;
use Dennisbusk\DebugNotary\Nova\Actions\SetEstimate;
use Dennisbusk\DebugNotary\Nova\Filters\BugEstimateFilter;
use Dennisbusk\DebugNotary\Nova\Filters\BugSeverityFilter;
use Dennisbusk\DebugNotary\Nova\Filters\BugStatusFilter;
use Dennisbusk\DebugNotary\Nova\Filters\BugTypeFilter;
use Dennisbusk\DebugNotary\Nova\Metrics\BugsBySeverityPartition;
use Dennisbusk\DebugNotary\Nova\Metrics\BugsTrendPerDay;
use Dennisbusk\DebugNotary\Nova\Metrics\UnresolvedBugsValue;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Line;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class RecordedBug extends Resource {

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<RecordedBugModel>
     */
    public static $model = RecordedBugModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'message';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search
        = [
            'id',
            'message',
            'file',
            'url',
            'hash',
            'user_note',
        ];

    /**
     * Get the logical group associated with the resource.
     */
    public static function group(): string {
        return __(config('debug-notary.nova.group', 'System'));
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string {
        return __('Debug Notary');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string {
        return __('Recorded Bug');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields( NovaRequest $request ): array {
        return [
            ID::make()->sortable(),

            Badge::make(__('Status'), 'status')
                 ->map([
                     BugStatus::OPEN->value        => 'danger',
                     BugStatus::IN_PROGRESS->value => 'info',
                     BugStatus::PENDING->value     => 'warning',
                     BugStatus::RESOLVED->value    => 'success',
                     BugStatus::WONT_FIX->value    => 'danger',
                     'open'                        => 'danger',
                     'in_progress'                 => 'info',
                     'pending'                     => 'warning',
                     'resolved'                    => 'success',
                     'wont_fix'                    => 'danger',
                 ])
                 ->labels([
                     BugStatus::OPEN->value        => BugStatus::OPEN->label(),
                     BugStatus::IN_PROGRESS->value => BugStatus::IN_PROGRESS->label(),
                     BugStatus::PENDING->value     => BugStatus::PENDING->label(),
                     BugStatus::RESOLVED->value    => BugStatus::RESOLVED->label(),
                     BugStatus::WONT_FIX->value    => BugStatus::WONT_FIX->label(),
                     'open'                        => __('Open'),
                     'in_progress'                 => __('In Progress'),
                     'pending'                     => __('Pending'),
                     'resolved'                    => __('Resolved'),
                     'wont_fix'                    => __('Won\'t Fix'),
                 ])
                 ->sortable(),

            Badge::make(__('Severity'), 'severity')
                 ->map([
                     BugSeverity::LOW->value      => 'info',
                     BugSeverity::MEDIUM->value   => 'warning',
                     BugSeverity::HIGH->value     => 'warning',
                     BugSeverity::CRITICAL->value => 'danger',
                     'debug'                      => 'info',
                     'info'                       => 'info',
                     'notice'                     => 'info',
                     'low'                        => 'info',
                     'warning'                    => 'warning',
                     'medium'                     => 'warning',
                     'error'                      => 'danger',
                     'high'                       => 'warning',
                     'critical'                   => 'danger',
                     'alert'                      => 'danger',
                     'emergency'                  => 'danger',
                 ])
                 ->labels([
                     BugSeverity::LOW->value      => BugSeverity::LOW->label(),
                     BugSeverity::MEDIUM->value   => BugSeverity::MEDIUM->label(),
                     BugSeverity::HIGH->value     => BugSeverity::HIGH->label(),
                     BugSeverity::CRITICAL->value => BugSeverity::CRITICAL->label(),
                     'debug'                      => __('Debug'),
                     'info'                       => __('Info'),
                     'notice'                     => __('Notice'),
                     'low'                        => __('Low'),
                     'warning'                    => __('Warning'),
                     'medium'                     => __('Medium'),
                     'error'                      => __('Error'),
                     'high'                       => __('High'),
                     'critical'                   => __('Critical'),
                     'alert'                      => __('Alert'),
                     'emergency'                  => __('Emergency'),
                 ])
                 ->sortable(),

            Badge::make(__('Type'), 'log_type')
                 ->map([
                     'system'     => 'info',
                     'manual'     => 'success',
                     'notary'     => 'success',
                     'javascript' => 'warning',
                     'error'      => 'danger',
                 ])
                 ->labels([
                     'system'     => __('System'),
                     'manual'     => __('Manual'),
                     'notary'     => __('Notary'),
                     'javascript' => __('JavaScript'),
                     'error'      => __('Error'),
                 ])
                 ->sortable(),

            Number::make(__('Count'), 'count')
                  ->sortable(),

            Stack::make(__('Estimate'), [
                Line::make(__('Estimate'), fn() => $this->formattedEstimate() ?: '-')
                    ->asHeading(),
                Line::make(__('Status'), function () {
                    if ($this->isEstimateAccepted()) {
                        return '✓ ' . __('debug-notary::messages.estimate_accepted');
                    }
                    if ($this->formattedEstimate()) {
                        return __('debug-notary::messages.estimate_pending');
                    }
                    return null;
                })->asSmall(),
            ])->onlyOnIndex(),

            Number::make(__('debug-notary::messages.hours'), 'estimate_hours')
                  ->min(0)
                  ->step(1)
                  ->hideFromIndex(),

            Number::make(__('debug-notary::messages.minutes'), 'estimate_minutes')
                  ->min(0)
                  ->max(59)
                  ->step(1)
                  ->hideFromIndex(),

            Text::make(__('Estimate'), fn() => $this->formattedEstimate() ?: __('debug-notary::messages.estimate_not_set'))
                ->onlyOnDetail(),

            Badge::make(__('Estimate Status'), function () {
                if ($this->isEstimateAccepted()) {
                    return 'accepted';
                }
                if ($this->formattedEstimate()) {
                    return 'pending';
                }
                return 'none';
            })->map([
                'accepted' => 'success',
                'pending' => 'warning',
                'none' => 'info',
            ])->labels([
                'accepted' => __('debug-notary::messages.estimate_accepted'),
                'pending' => __('debug-notary::messages.estimate_pending'),
                'none' => __('debug-notary::messages.estimate_not_set'),
            ])->onlyOnDetail(),

            DateTime::make(__('Estimate Accepted At'), 'estimate_accepted_at')
                    ->onlyOnDetail(),

            Text::make(__('Estimate Accepted By'), fn() => $this->estimateAcceptedByName() ?? '-')
                ->onlyOnDetail(),

            Stack::make(__('Message'), [
                Line::make(__('Message'), 'message')
                    ->displayUsing(fn( $val ) => Str::limit($val, 100))
                    ->asHeading(),
                Line::make(__('Location'), fn() => $this->file ? "{$this->file}:{$this->line}" : null)
                    ->asSmall(),
            ])->onlyOnIndex(),

            Textarea::make(__('Message'), 'message')
                    ->alwaysShow()
                    ->hideFromIndex(),

            Text::make(__('File'), 'file')
                ->hideFromIndex(),

            Number::make(__('Line'), 'line')
                  ->hideFromIndex(),

            Text::make(__('URL'), 'url')
                ->displayUsing(fn( $val ) => $val ? Str::limit($val, 50) : '-')
                ->sortable(),

            DateTime::make(__('Last Seen'), 'last_seen_at')
                    ->sortable(),

            DateTime::make(__('First Seen'), 'created_at')
                    ->sortable()
                    ->hideFromIndex(),

            Text::make(__('User Role'), 'user_role')
                ->hideFromIndex(),

            Text::make(__('User Note'), 'user_note')
                ->hideFromIndex(),

            Code::make(__('Browser Data'), 'browser_data')
                ->json()
                ->hideFromIndex(),

            Code::make(__('Trend Data'), 'trend_data')
                ->json()
                ->hideFromIndex(),

            Code::make(__('Stack Trace'), 'stack_trace')
                ->hideFromIndex(),

            Image::make(__('Screenshot'), 'screenshot_url')
                 ->thumbnail(fn() => $this->screenshot_url)
                 ->preview(fn() => $this->screenshot_url)
                 ->hideFromIndex(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards( NovaRequest $request ): array {
        return [
            new UnresolvedBugsValue,
            new BugsBySeverityPartition,
            new BugsTrendPerDay,
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters( NovaRequest $request ): array {
        return [
            new BugStatusFilter,
            new BugSeverityFilter,
            new BugTypeFilter,
            new BugEstimateFilter,
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions( NovaRequest $request ): array {
        return [
            (new SetEstimate)->showInline(),
            (new AcceptEstimate)->showInline(),
            new MarkAsResolved,
            new MarkAsInProgress,
            new MarkAsOpen,
            new MarkAsWontFix,
        ];
    }
}
