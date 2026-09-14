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

            Text::make(__('Handlinger & Tidsestimat'), fn() => $this->renderControlPanelHtml())
                ->asHtml()
                ->onlyOnDetail(),

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

    /**
     * Render interactive control panel with Status, Assignee, and Time Estimate matching the original design.
     */
    protected function renderControlPanelHtml(): string
    {
        /** @var RecordedBugModel $bug */
        $bug = $this->resource;
        $id = $bug->id;
        $prefix = trim(config('debug-notary.route_prefix', 'laravel-debug-notary'), '/');
        $baseUrl = '/' . $prefix;

        $userModel = config('debug-notary.user_model')
            ?: config('auth.providers.users.model')
            ?: \App\Models\User::class;
        $users = class_exists($userModel) ? $userModel::all() : collect();

        $statuses = [
            'open' => __('debug-notary::messages.status_open'),
            'in_progress' => __('debug-notary::messages.status_in_progress'),
            'pending' => __('debug-notary::messages.status_pending'),
            'resolved' => __('debug-notary::messages.status_resolved'),
            'wont_fix' => __('debug-notary::messages.status_wont_fix'),
        ];

        $currentStatus = is_object($bug->status) ? $bug->status->value : (string) $bug->status;

        $statusButtonsHtml = '';
        foreach ($statuses as $val => $label) {
            $isActive = $currentStatus === $val;
            $btnClass = $isActive
                ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-600'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700';
            $statusButtonsHtml .= '<button type="button" onclick="debugNotaryUpdateStatus(' . $id . ', \'' . $val . '\')" class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider transition-all duration-150 ' . $btnClass . '">' . e($label) . '</button>';
        }

        $userOptionsHtml = '<option value="">' . e(__('debug-notary::messages.nobody')) . '</option>';
        foreach ($users as $u) {
            $selected = ($bug->assigned_to_id == $u->id) ? ' selected' : '';
            $userOptionsHtml .= '<option value="' . e($u->id) . '"' . $selected . '>' . e($u->name) . '</option>';
        }

        $hoursVal = $bug->estimate_hours ?? '';
        $minutesVal = $bug->estimate_minutes ?? '';
        $isAccepted = $bug->isEstimateAccepted();
        $disabledAttr = $isAccepted ? ' disabled' : '';

        $estimateActionsHtml = '';
        if (! $isAccepted) {
            $estimateActionsHtml .= '<button type="button" onclick="debugNotaryUpdateEstimate(' . $id . ')" class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-[10px] font-bold uppercase tracking-wider transition-colors shadow-sm">' . e(__('debug-notary::messages.save_estimate')) . '</button>';
            if ($bug->formattedEstimate()) {
                $confirmMsg = addslashes(__('debug-notary::messages.confirm_accept_estimate', ['estimate' => $bug->formattedEstimate()]));
                $estimateActionsHtml .= '<button type="button" onclick="if(confirm(\'' . $confirmMsg . '\')) { debugNotaryAcceptEstimate(' . $id . '); }" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[10px] font-bold uppercase tracking-wider transition-colors shadow-sm"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' . e(__('debug-notary::messages.accept_estimate')) . '</button>';
            }
        } else {
            $acceptedText = __('debug-notary::messages.estimate_accepted_by', [
                'name' => $bug->estimateAcceptedByName() ?? __('debug-notary::messages.nobody'),
                'time' => $bug->estimate_accepted_at?->format('d/m/Y H:i'),
            ]);
            $estimateActionsHtml .= '<div class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 text-[11px] text-green-700 dark:text-green-300 font-medium"><svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>' . e($acceptedText) . '</span></div>';
        }

        $changeStatusTitle = e(__('debug-notary::messages.change_status'));
        $assignToTitle = e(__('debug-notary::messages.assign_to'));
        $timeEstimateTitle = e(__('debug-notary::messages.time_estimate'));
        $hoursLabel = e(__('debug-notary::messages.hours'));
        $minutesLabel = e(__('debug-notary::messages.minutes'));

        return <<<HTML
<div class="flex flex-wrap items-center gap-6 py-2">
    <!-- Status Selector -->
    <div class="flex flex-col gap-1.5">
        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">{$changeStatusTitle}</span>
        <div class="flex flex-wrap gap-1.5">
            {$statusButtonsHtml}
        </div>
    </div>

    <!-- Assignee Selector -->
    <div class="flex flex-col gap-1.5">
        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">{$assignToTitle}</span>
        <select onchange="debugNotaryUpdateAssignee({$id}, this.value)" class="block w-48 pl-3 pr-10 py-1 text-[11px] border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md dark:bg-gray-800 dark:text-gray-300">
            {$userOptionsHtml}
        </select>
    </div>

    <!-- Estimate Section -->
    <div class="flex flex-col gap-1.5">
        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">{$timeEstimateTitle}</span>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1">
                <input type="number" min="0" value="{$hoursVal}" placeholder="0"{$disabledAttr} id="nova_estimate_hours_{$id}" class="w-14 px-2 py-1 text-[11px] border rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-gray-700 focus:ring-indigo-500 focus:border-indigo-500" />
                <span class="text-xs text-gray-500 font-medium">{$hoursLabel}</span>
                <input type="number" min="0" max="59" value="{$minutesVal}" placeholder="0"{$disabledAttr} id="nova_estimate_minutes_{$id}" class="w-14 px-2 py-1 text-[11px] border rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-gray-700 focus:ring-indigo-500 focus:border-indigo-500" />
                <span class="text-xs text-gray-500 font-medium">{$minutesLabel}</span>
            </div>
            {$estimateActionsHtml}
        </div>
    </div>
</div>

<script>
if (typeof window.debugNotaryUpdateStatus === 'undefined') {
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    window.debugNotaryUpdateStatus = function(bugId, status) {
        fetch('{$baseUrl}/' + bugId + '/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status: status })
        }).then(function(res) {
            if (res.ok) { window.location.reload(); }
            else { res.json().then(function(d) { alert(d.error || 'Fejl under opdatering'); }); }
        }).catch(function() { window.location.reload(); });
    };

    window.debugNotaryUpdateAssignee = function(bugId, assigneeId) {
        fetch('{$baseUrl}/' + bugId + '/assignee', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ assigned_to_id: assigneeId })
        }).then(function(res) {
            if (res.ok) { window.location.reload(); }
            else { res.json().then(function(d) { alert(d.error || 'Fejl under opdatering'); }); }
        }).catch(function() { window.location.reload(); });
    };

    window.debugNotaryUpdateEstimate = function(bugId) {
        var h = document.getElementById('nova_estimate_hours_' + bugId)?.value;
        var m = document.getElementById('nova_estimate_minutes_' + bugId)?.value;
        fetch('{$baseUrl}/' + bugId + '/estimate', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ estimate_hours: h, estimate_minutes: m })
        }).then(function(res) {
            if (res.ok) { window.location.reload(); }
            else { res.json().then(function(d) { alert(d.error || 'Fejl under opdatering'); }); }
        }).catch(function() { window.location.reload(); });
    };

    window.debugNotaryAcceptEstimate = function(bugId) {
        fetch('{$baseUrl}/' + bugId + '/estimate/accept', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function(res) {
            if (res.ok) { window.location.reload(); }
            else { res.json().then(function(d) { alert(d.error || 'Fejl under godkendelse'); }); }
        }).catch(function() { window.location.reload(); });
    };
}
</script>
HTML;
    }
}
