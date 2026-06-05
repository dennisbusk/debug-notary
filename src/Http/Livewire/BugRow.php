<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

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

    public function updateStatus($status)
    {
        $this->bug->update(['status' => $status]);
        $this->dispatch('statusUpdated');
    }

    public function openBug()
    {
        $this->dispatch('open-bug-modal', bugId: $this->bug->id);
    }

    public function render()
    {
        return view('debug-notary::livewire.bug-row', [
            'statuses' => [
                RecordedBug::STATUS_OPEN,
                RecordedBug::STATUS_IN_PROGRESS,
                RecordedBug::STATUS_RESOLVED,
            ],
        ]);
    }
}
