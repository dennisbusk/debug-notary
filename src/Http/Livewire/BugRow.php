<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

use Dennisbusk\DebugNotary\Enums\BugStatus;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Livewire\Component;

class BugRow extends Component
{
    public RecordedBug $bug;

    public bool $selected = false;

    protected $listeners = ['statusUpdated' => '$refresh'];

    public function mount(RecordedBug $bug, bool $selected = false)
    {
        $this->bug = $bug;
        $this->selected = $selected;
    }

    public function toggleSelect()
    {
        $this->selected = ! $this->selected;
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('toggleSelect', bugId: $this->bug->id);
        } elseif (method_exists($this, 'emitUp')) {
            $this->emitUp('toggleSelect', $this->bug->id);
        } else {
            $this->emit('toggleSelect', $this->bug->id);
        }
    }

    public function updateStatus($status)
    {
        $this->bug->update(['status' => $status]);
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('statusUpdated');
        } elseif (method_exists($this, 'emit')) {
            $this->emit('statusUpdated');
        }
    }

    public function render()
    {
        return view('debug-notary::livewire.bug-row', [
            'statuses' => BugStatus::cases(),
            'columns' => config('debug-notary.list_view.columns', []),
        ]);
    }
}
