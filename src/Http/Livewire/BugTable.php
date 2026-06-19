<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

use Dennisbusk\DebugNotary\Enums\BugSeverity;
use Dennisbusk\DebugNotary\Enums\BugStatus;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BugTable extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $tag = '';

    #[Url(history: true)]
    public $severity = '';

    #[Url(history: true)]
    public $logType = '';

    #[Url(history: true)]
    public $status = '';

    public array $selected = [];

    public bool $selectAll = false;

    public bool $allMatchingSelected = false;

    protected $listeners
        = [
            'bugDeleted' => '$refresh',
            'statusUpdated' => '$refresh',
            'bugsDeleted' => 'onBugsDeleted',
        ];

    public function onBugsDeleted()
    {
        $this->reset(['selected', 'selectAll', 'allMatchingSelected']);
        $this->dispatchSelectionUpdated();
        $this->render();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleSelectAllPage()
    {
        if ($this->selectAll) {
            $this->selected = $this->bugs->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
            $this->allMatchingSelected = false;
        }
        $this->dispatchSelectionUpdated();
    }

    public function updatedSelected()
    {
        $this->selectAll = count($this->selected) === count($this->bugs->items());
        if (! $this->selectAll) {
            $this->allMatchingSelected = false;
        }
        $this->dispatchSelectionUpdated();
    }

    public function selectAllMatching()
    {
        $this->allMatchingSelected = true;
        $this->dispatchSelectionUpdated();
    }

    public function clearSelection()
    {
        $this->reset(['selected', 'selectAll', 'allMatchingSelected']);
        $this->dispatchSelectionUpdated();
    }

    protected function dispatchSelectionUpdated()
    {
        $this->dispatch('selectionUpdated',
            selected: $this->selected,
            allMatchingSelected: $this->allMatchingSelected,
            filters: [
                'search' => $this->search,
                'tag' => $this->tag,
                'severity' => $this->severity,
                'logType' => $this->logType,
                'status' => $this->status,
            ]
        )->to(BugBulkActions::class);
    }

    public function resetFilters()
    {
        $this->reset(['search', 'tag', 'severity', 'logType', 'status']);
        $this->resetPage();
    }

    public function updateStatus($bugId, $status)
    {
        RecordedBug::where('id', $bugId)->update(['status' => $status]);
    }

    public function baseQuery()
    {
        return RecordedBug::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('message', 'like', '%'.$this->search.'%')
                        ->orWhere('file', 'like', '%'.$this->search.'%')
                        ->orWhere('user_note', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->tag, function ($query) {
                $query->whereJsonContains('tags', $this->tag);
            })
            ->when($this->severity, function ($query) {
                $query->where('severity', $this->severity);
            })
            ->when($this->logType, function ($query) {
                $query->where('log_type', $this->logType);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            });
    }

    public function getBugsProperty()
    {
        return $this->baseQuery()
            ->with('user')
            ->latest('last_seen_at')
            ->paginate(20);
    }

    public function getTrendDataProperty()
    {
        return Cache::remember('debug-notary-trend-data', 300, function () {
            $allBugs = RecordedBug::whereNotNull('trend_data')
                ->where('last_seen_at', '>=', now()->subDays(30))
                ->select('trend_data')
                ->get();
            $aggregatedTrend = [];

            foreach ($allBugs as $bug) {
                $trend = $bug->trend_data;
                if (! is_array($trend)) {
                    continue;
                }
                foreach ($trend as $date => $count) {
                    $aggregatedTrend[$date] = ($aggregatedTrend[$date] ?? 0) + $count;
                }
            }

            ksort($aggregatedTrend);

            return array_slice($aggregatedTrend, -30, null, true);
        });
    }

    public function getTopFilesProperty()
    {
        return RecordedBug::query()
            ->whereNotNull('file')
            ->select('file', DB::raw('sum(count) as total'))
            ->groupBy('file')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    public function getTopRoutesProperty()
    {
        return RecordedBug::query()
            ->whereNotNull('url')
            ->select('url', DB::raw('sum(count) as total'))
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        $allTags = Cache::remember('debug-notary-all-tags', 300, function () {
            return RecordedBug::whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->filter()
                ->values();
        });

        return view('debug-notary::livewire.bug-table', [
            'bugs' => $this->bugs,
            'allTags' => $allTags,
            'severities' => BugSeverity::cases(),
            'statuses' => BugStatus::cases(),
            'topFiles' => $this->topFiles,
            'topRoutes' => $this->topRoutes,
            'trendData' => $this->trendData,
            'columns' => config('debug-notary.list_view.columns', []),
        ]);
    }
}
