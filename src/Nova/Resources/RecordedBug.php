<?php

namespace Dennisbusk\DebugNotary\Nova\Resources;

use Dennisbusk\DebugNotary\Enums\BugSeverity;
use Dennisbusk\DebugNotary\Enums\BugStatus;
use Dennisbusk\DebugNotary\Models\RecordedBug as RecordedBugModel;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsInProgress;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsOpen;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsResolved;
use Dennisbusk\DebugNotary\Nova\Actions\MarkAsWontFix;
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
                 ])
                 ->labels([
                     BugStatus::OPEN->value        => BugStatus::OPEN->label(),
                     BugStatus::IN_PROGRESS->value => BugStatus::IN_PROGRESS->label(),
                     BugStatus::PENDING->value     => BugStatus::PENDING->label(),
                     BugStatus::RESOLVED->value    => BugStatus::RESOLVED->label(),
                     BugStatus::WONT_FIX->value    => BugStatus::WONT_FIX->label(),
                 ])
                 ->sortable(),

            Badge::make(__('Severity'), 'severity')
                 ->map([
                     BugSeverity::LOW->value      => 'info',
                     BugSeverity::MEDIUM->value   => 'warning',
                     BugSeverity::HIGH->value     => 'warning',
                     BugSeverity::CRITICAL->value => 'danger',
                 ])
                 ->labels([
                     BugSeverity::LOW->value      => BugSeverity::LOW->label(),
                     BugSeverity::MEDIUM->value   => BugSeverity::MEDIUM->label(),
                     BugSeverity::HIGH->value     => BugSeverity::HIGH->label(),
                     BugSeverity::CRITICAL->value => BugSeverity::CRITICAL->label(),
                 ])
                 ->sortable(),

            Badge::make(__('Type'), 'log_type')
                 ->map([
                     'system'     => 'info',
                     'manual'     => 'success',
                     'javascript' => 'warning',
                 ])
                 ->sortable(),

            Number::make(__('Count'), 'count')
                  ->sortable(),

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
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions( NovaRequest $request ): array {
        return [
            new MarkAsResolved,
            new MarkAsInProgress,
            new MarkAsOpen,
            new MarkAsWontFix,
        ];
    }
}
