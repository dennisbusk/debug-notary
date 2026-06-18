<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

use Dennisbusk\DebugNotary\Enums\BugStatus;
use Dennisbusk\DebugNotary\Jobs\NotifyBugActivityJob;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Livewire\Component;
use Livewire\WithFileUploads;

class BugDetail extends Component {

    use WithFileUploads;

    public RecordedBug $bug;

    public $newMessage = '';

    public $attachment;

    protected $rules
        = [
            'newMessage' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ];

    public function mount( $bugId ) {
        $this->bug = RecordedBug::with([ 'user', 'messages.user', 'assignedTo' ])->findOrFail($bugId);
        $this->markMessagesAsRead();
    }

    public function sendMessage() {
        if ( empty(trim($this->newMessage)) && !$this->attachment ) {
            return;
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ( $this->attachment ) {
            $attachmentPath = $this->attachment->store('debug-notary/attachments', 'public');
            $attachmentType = $this->attachment->getClientOriginalExtension();
        }

        $message = $this->bug->messages()->create([
            'user_id'         => auth()->id(),
            'message'         => $this->newMessage ?? '',
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        // Send notifikation
        NotifyBugActivityJob::dispatch(
            $this->bug,
            'new_message',
            [
                'sender'  => auth()->user()->name ?? 'System',
                'message' => $this->newMessage ?: ( $this->attachment ? 'Vedhæftet fil' : '' ),
            ]
        );

        $this->newMessage = '';
        $this->attachment = null;
        $this->bug->load('messages.user');
    }

    public function markMessagesAsRead() {
        // Marker beskeder fra andre som læst når man åbner siden
        if ( auth()->check() ) {
            $this->bug->messages()
                      ->where('user_id', '!=', auth()->id())
                      ->where('is_read', false)
                      ->update([ 'is_read' => true ]);
        }
    }

    public function deleteBug() {
        if ( $this->bug ) {
            $this->bug->delete();
            session()->flash('message', __('debug-notary::messages.bug_deleted'));

            return redirect()->route('debug-notary.index');
        }
    }

    public function updateStatus( $status ) {
        if ( $this->bug && $this->bug->status->value !== $status ) {
            $oldStatus = $this->bug->status;
            $this->bug->update([ 'status' => $status ]);

            // For at få det nye status objekt med labels
            $this->bug->refresh();
            $newStatus = $this->bug->status;

            // Log historik
            $this->bug->messages()->create([
                'user_id' => null, // System besked
                'message' => __('debug-notary::messages.history_status_changed', [
                    'old'  => $oldStatus->label(),
                    'new'  => $newStatus->label(),
                    'user' => auth()->user()->name ?? 'System',
                ]),
            ]);

            $this->bug->load('messages.user');
            $this->dispatch('statusUpdated');
        }
    }

    public function updateAssignee( $userId ) {
        if ( $this->bug ) {
            $oldAssigneeId = $this->bug->assigned_to_id;
            if ( $oldAssigneeId == $userId ) {
                return;
            }

            $this->bug->update([ 'assigned_to_id' => $userId ?: null ]);
            $this->bug->load('assignedTo');

            $assigneeName = $this->bug->assignedTo->name ?? __('debug-notary::messages.nobody');

            // Log historik
            $this->bug->messages()->create([
                'user_id' => null, // System besked
                'message' => __('debug-notary::messages.history_assignee_changed', [
                    'name' => $assigneeName,
                    'user' => auth()->user()->name ?? 'System',
                ]),
            ]);

            // Send notifikation
            if ( $userId ) {
                NotifyBugActivityJob::dispatch(
                    $this->bug,
                    'assigned',
                    [
                        'user' => auth()->user()->name ?? 'System',
                    ]
                );
            }

            $this->bug->load('messages.user');
        }
    }

    public function getUsersProperty() {
        $userModel = config('auth.providers.users.model');

        return $userModel::all();
    }

    public function render() {
        return view('debug-notary::livewire.bug-detail');
    }
}
