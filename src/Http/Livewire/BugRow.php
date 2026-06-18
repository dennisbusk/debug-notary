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

    public function updateStatus($status)
    {
        $this->bug->update(['status' => $status]);
        $this->dispatch('statusUpdated');
    }

    public function render()
    {
        return view('debug-notary::livewire.bug-row', [
            'statuses' => BugStatus::cases(),
        ]);
    }
}
