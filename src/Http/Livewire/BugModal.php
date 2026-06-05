<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

use Dennisbusk\DebugNotary\Models\RecordedBug;
use Livewire\Component;

class BugModal extends Component
{
    public ?RecordedBug $bug = null;

    public bool $isOpen = false;

    protected $listeners = ['open-bug-modal' => 'loadBug'];

    public function loadBug($bugId)
    {
        $this->bug = RecordedBug::with('user')->find($bugId);
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->bug = null;
    }

    public function deleteBug()
    {
        if ($this->bug) {
            $this->bug->delete();
            $this->dispatch('bugDeleted');
            $this->closeModal();
            session()->flash('message', __('debug-notary::messages.bug_deleted'));
        }
    }

    public function updateStatus($status)
    {
        if ($this->bug) {
            $this->bug->update(['status' => $status]);
            $this->dispatch('statusUpdated');
        }
    }

    public function render()
    {
        return view('debug-notary::livewire.bug-modal');
    }
}
