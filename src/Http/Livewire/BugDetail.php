<?php

namespace Dennisbusk\DebugNotary\Http\Livewire;

use App\Models\User;
use Dennisbusk\DebugNotary\Facades\DebugNotary as DebugNotaryFacade;
use Dennisbusk\DebugNotary\Jobs\NotifyBugActivityJob;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Livewire\Component;
use Livewire\WithFileUploads;

class BugDetail extends Component
{
    use WithFileUploads;

    public RecordedBug $bug;

    public ?int $estimateHours = null;

    public ?int $estimateMinutes = null;

    public $newMessage = '';

    public $attachment;

    protected $rules
        = [
            'newMessage' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ];

    public function mount($bugId)
    {
        $this->bug = RecordedBug::with(['user', 'messages.user', 'assignedTo', 'estimateAcceptedBy'])->findOrFail($bugId);
        $this->estimateHours = $this->bug->estimate_hours;
        $this->estimateMinutes = $this->bug->estimate_minutes;
        $this->markMessagesAsRead();
    }

    public function sendMessage()
    {
        if (empty(trim($this->newMessage)) && ! $this->attachment) {
            return;
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($this->attachment) {
            $attachmentPath = $this->attachment->store('debug-notary/attachments', 'local');
            $attachmentType = $this->attachment->getClientOriginalExtension();
        }

        $message = $this->bug->messages()->create([
            'user_id' => auth()->id(),
            'message' => $this->newMessage ?? '',
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        // Synkroniser til Central
        DebugNotaryFacade::syncMessageToCentral($this->bug, $message);

        // Send notifikation
        NotifyBugActivityJob::dispatch(
            $this->bug,
            'new_message',
            [
                'sender' => auth()->user()->name ?? 'System',
                'message' => $this->newMessage ?: ($this->attachment ? 'Vedhæftet fil' : ''),
            ]
        );

        $this->newMessage = '';
        $this->attachment = null;
        $this->bug->load('messages.user');
    }

    public function markMessagesAsRead()
    {
        // Marker beskeder fra andre som læst når man åbner siden
        if (auth()->check()) {
            $this->bug->messages()
                ->where('user_id', '!=', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }
    }

    public function deleteBug()
    {
        if ($this->bug) {
            $this->bug->delete();
            session()->flash('message', __('debug-notary::messages.bug_deleted'));

            return redirect()->route('debug-notary.index');
        }
    }

    public function updateStatus($status)
    {
        if ($this->bug && $this->bug->status->value !== $status) {
            $oldStatus = $this->bug->status;
            $this->bug->update(['status' => $status]);

            // For at få det nye status objekt med labels
            $this->bug->refresh();
            $newStatus = $this->bug->status;

            // Synkroniser til Central
            DebugNotaryFacade::syncBugUpdateToCentral($this->bug);

            // Log historik
            $this->bug->messages()->create([
                'user_id' => null, // System besked
                'message' => __('debug-notary::messages.history_status_changed', [
                    'old' => $oldStatus->label(),
                    'new' => $newStatus->label(),
                    'user' => auth()->user()->name ?? 'System',
                ]),
            ]);

            $this->bug->load('messages.user');
            if (method_exists($this, 'dispatch')) {
                $this->dispatch('statusUpdated');
            } elseif (method_exists($this, 'emit')) {
                $this->emit('statusUpdated');
            }
        }
    }

    public function updateAssignee($userId)
    {
        if ($this->bug) {
            $oldAssigneeId = $this->bug->assigned_to_id;
            if ($oldAssigneeId == $userId) {
                return;
            }

            $this->bug->update(['assigned_to_id' => $userId ?: null]);
            $this->bug->load(['assignedTo', 'messages.user']);

            // Synkroniser til Central
            DebugNotaryFacade::syncBugUpdateToCentral($this->bug);

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
            if ($userId) {
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

    public function updateEstimate()
    {
        if ($this->bug->isEstimateAccepted()) {
            session()->flash('error', __('debug-notary::messages.estimate_locked_error'));

            return;
        }

        $this->validate([
            'estimateHours' => 'nullable|integer|min:0',
            'estimateMinutes' => 'nullable|integer|min:0|max:59',
        ]);

        $newHours = $this->estimateHours !== '' && $this->estimateHours !== null ? (int) $this->estimateHours : null;
        $newMinutes = $this->estimateMinutes !== '' && $this->estimateMinutes !== null ? (int) $this->estimateMinutes : null;

        if ($newHours === 0 && $newMinutes === 0) {
            $newHours = null;
            $newMinutes = null;
            $this->estimateHours = null;
            $this->estimateMinutes = null;
        }

        if ($newHours !== $this->bug->estimate_hours || $newMinutes !== $this->bug->estimate_minutes) {
            $this->bug->update([
                'estimate_hours' => $newHours,
                'estimate_minutes' => $newMinutes,
            ]);

            $this->bug->refresh();

            // Synkroniser til Central
            DebugNotaryFacade::syncBugUpdateToCentral($this->bug);

            $userName = auth()->user()?->name ?? 'System';
            $formatted = $this->bug->formattedEstimate();

            if ($formatted) {
                $this->bug->messages()->create([
                    'user_id' => null, // System besked
                    'message' => __('debug-notary::messages.history_estimate_set', [
                        'estimate' => $formatted,
                        'user' => $userName,
                    ]),
                ]);
            } else {
                $this->bug->messages()->create([
                    'user_id' => null, // System besked
                    'message' => __('debug-notary::messages.history_estimate_removed', [
                        'user' => $userName,
                    ]),
                ]);
            }

            $this->bug->load('messages.user');
            if (method_exists($this, 'dispatch')) {
                $this->dispatch('estimateUpdated');
            } elseif (method_exists($this, 'emit')) {
                $this->emit('estimateUpdated');
            }
        }
    }

    public function acceptEstimate()
    {
        if ($this->bug->isEstimateAccepted()) {
            return;
        }

        if ($this->bug->estimate_hours === null && $this->bug->estimate_minutes === null) {
            return;
        }

        $acceptedAt = now();
        $user = auth()->user();

        $this->bug->update([
            'estimate_accepted_at' => $acceptedAt,
            'estimate_accepted_by_id' => $user?->id,
            'estimate_accepted_by_name' => $user?->name ?? 'Ukendt',
        ]);

        $this->bug->refresh();

        // Synkroniser til Central
        DebugNotaryFacade::syncBugUpdateToCentral($this->bug);

        $formatted = $this->bug->formattedEstimate() ?: '0 timer 0 minutter';
        $dateStr = $acceptedAt->format('d/m/Y H:i');
        $userName = $user?->name ?? 'Ukendt';

        // Log historik
        $this->bug->messages()->create([
            'user_id' => null, // System besked
            'message' => __('debug-notary::messages.history_estimate_accepted', [
                'estimate' => $formatted,
                'user' => $userName,
                'time' => $dateStr,
            ]),
        ]);

        $this->bug->load(['estimateAcceptedBy', 'messages.user']);
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('estimateAccepted');
        } elseif (method_exists($this, 'emit')) {
            $this->emit('estimateAccepted');
        }
    }

    public function getUsersProperty()
    {
        $userModel = config('debug-notary.user_model')
            ?: config('auth.providers.users.model')
            ?: User::class;

        return class_exists($userModel) ? $userModel::all() : collect();
    }

    public function render()
    {
        return view('debug-notary::livewire.bug-detail');
    }
}
