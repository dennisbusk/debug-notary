<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

use Dennisbusk\DebugNotary\Models\RecordedBug;
use Livewire\Component;

class BugBulkActions extends Component
{
    public array $selected = [];

    public bool $allMatchingSelected = false;

    public array $filters = [];

    protected $listeners
        = [
            'selectionUpdated' => 'updateSelection',
        ];

    public function updateSelection($selected, $allMatchingSelected, $filters)
    {
        $this->selected = $selected;
        $this->allMatchingSelected = $allMatchingSelected;
        $this->filters = $filters;
    }

    public function selectAllMatching()
    {
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('selectAllMatching');
        } elseif (method_exists($this, 'emitUp')) {
            $this->emitUp('selectAllMatching');
        } else {
            $this->emit('selectAllMatching');
        }
    }

    public function clearSelection()
    {
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('clearSelection');
        } elseif (method_exists($this, 'emitUp')) {
            $this->emitUp('clearSelection');
        } else {
            $this->emit('clearSelection');
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selected) && ! $this->allMatchingSelected) {
            return;
        }

        if ($this->allMatchingSelected) {
            $this->deleteAllMatching();

            return;
        }

        RecordedBug::whereIn('id', $this->selected)->delete();
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('bugsDeleted');
        } elseif (method_exists($this, 'emit')) {
            $this->emit('bugsDeleted');
        }
        $this->reset(['selected', 'allMatchingSelected']);
        session()->flash('message', __('debug-notary::messages.bugs_deleted', ['count' => count($this->selected)]));
    }

    public function deleteAllMatching()
    {
        $query = RecordedBug::query()
            ->when($this->filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', '%'.$search.'%')
                        ->orWhere('file', 'like', '%'.$search.'%')
                        ->orWhere('user_note', 'like', '%'.$search.'%');
                });
            })
            ->when($this->filters['tag'] ?? null, function ($query, $tag) {
                $query->whereJsonContains('tags', $tag);
            })
            ->when($this->filters['severity'] ?? null, function ($query, $severity) {
                $query->where('severity', $severity);
            })
            ->when($this->filters['logType'] ?? null, function ($query, $logType) {
                $query->where('log_type', $logType);
            })
            ->when($this->filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            });

        $count = $query->count();
        $query->delete();

        if (method_exists($this, 'dispatch')) {
            $this->dispatch('bugsDeleted');
        } elseif (method_exists($this, 'emit')) {
            $this->emit('bugsDeleted');
        }
        $this->reset(['selected', 'allMatchingSelected']);
        session()->flash('message', __('debug-notary::messages.bugs_deleted', ['count' => $count]));
    }

    public function render()
    {
        return view('debug-notary::livewire.bug-bulk-actions');
    }
}
