<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

use Dennisbusk\DebugNotary\Models\RecordedBug;
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

    public ?int $openBugId = null;

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

    public function openBug($id)
    {
        $this->openBugId = $id;
        $this->dispatch('open-bug-modal', bugId: $id);
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

    public function render()
    {
        $allTags = RecordedBug::whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->filter();

        return view('debug-notary::livewire.bug-table', [
            'bugs' => $this->bugs,
            'allTags' => $allTags,
            'severities' => array_keys(RecordedBug::LEVELS),
            'statuses' => [
                RecordedBug::STATUS_OPEN,
                RecordedBug::STATUS_IN_PROGRESS,
                RecordedBug::STATUS_RESOLVED,
            ],
        ]);
    }
}
