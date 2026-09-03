<?php

namespace Dennisbusk\DebugNotary\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DebugNotaryController extends Controller
{
    public function index(Request $request)
    {
        if ($gate = config('debug-notary.access_gate')) {
            Gate::authorize($gate);
        }

        return view('debug-notary::index');
    }

    public function show($id)
    {
        if ($gate = config('debug-notary.access_gate')) {
            Gate::authorize($gate);
        }

        return view('debug-notary::show', ['id' => $id]);
    }

    public function storeNotary(Request $request)
    {
        // If it's a JS error logging or manual report via JSON
        if ($request->isJson() && ($request->input('log_type') === 'javascript' || $request->input('log_type') === 'manual')) {
            $logType = $request->input('log_type');
            $message = $request->input('message') ?? 'Log report.';
            $file = $request->input('file') ?? 'browser';
            $line = $request->input('line') ?? 0;
            $severity = $request->input('severity', 'error');
            $hash = RecordedBug::generateHash($message, $file, $line);

            $bug = RecordedBug::firstOrNew(['hash' => $hash]);
            $isNew = ! $bug->exists;

            if ($isNew) {
                $bug->message = $message;
                $bug->file = $file;
                $bug->line = $line;
                $bug->log_type = $logType;
                $bug->severity = $severity;
            }

            $userContext = $request->input('user_context') ?? DebugNotary::resolveUserContext();
            $bug->url = $request->input('url', request()->fullUrl());
            $bug->last_seen_at = now();
            $bug->count += 1;
            $bug->user_id = $userContext['user_id'] ?? null;
            $bug->user_role = $userContext['user_role'] ?? null;
            $bug->browser_data = DebugNotary::maskData($request->input('browser_data', []));

            $bug->updateTrendData();
            $bug->updateSeverity();
            $bug->save();

            if ($isNew) {
                // We use the Facade call to send notifications
                DebugNotary::notifyNewBug($bug);

                // Send to central
                $centralId = DebugNotary::sendToCentral([
                    'log_type' => $logType,
                    'message' => $message,
                    'severity' => $severity,
                    'file' => $file,
                    'line' => $line,
                    'stack_trace' => $request->input('browser_data.stack'),
                    'url' => $bug->url,
                    'screenshot' => $request->input('screenshot'),
                    'attachment' => $request->input('attachment'),
                    'attachment_name' => $request->input('attachment_name'),
                    'browser_data' => $bug->browser_data,
                    'user_context' => $userContext,
                    'note' => $request->input('note'),
                    'tags' => $request->input('tags', []),
                ]);

                if ($centralId) {
                    $bug->central_id = $centralId;
                    $bug->save();
                }
            }

            return response()->json(['success' => true]);
        }

        $request->validate([
            'screenshot' => ['nullable', 'max:10000000'], // Can be both string (base64) or file
            'note' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('screenshot')) {
            $request->validate([
                'screenshot' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            ]);
        }

        $note = $request->input('note');
        $tags = array_map('trim', explode(',', $request->input('tags', '')));
        $tags = array_filter($tags);
        $url = $request->input('url');
        $browserData = $request->input('browser_data');
        if (is_string($browserData)) {
            $browserData = json_decode($browserData, true);
        }

        $screenshotPath = null;
        $screenshotBase64 = null;
        $storageMode = config('debug-notary.screenshot_storage', 'base64');

        if ($request->hasFile('screenshot')) {
            $file = $request->file('screenshot');
            $imageName = 'notary_'.time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
            $screenshotPath = 'debug-notary/'.$imageName;

            Storage::disk('public')->put($screenshotPath, file_get_contents($file));

            if ($storageMode === 'base64' || $storageMode === 'both') {
                $screenshotBase64 = 'data:image/'.$file->getClientOriginalExtension().';base64,'.base64_encode(file_get_contents($file));
            }
        } elseif ($request->filled('screenshot')) {
            $screenshotData = $request->input('screenshot');

            if (Str::startsWith($screenshotData, 'data:image')) {
                if ($storageMode === 'base64' || $storageMode === 'both') {
                    $screenshotBase64 = $screenshotData;
                }

                if ($storageMode === 'file' || $storageMode === 'both') {
                    $extension = explode('/', explode(':', substr($screenshotData, 0, Str::position($screenshotData, ';')))[1])[1];
                    $image = str_replace('data:image/'.$extension.';base64,', '', $screenshotData);
                    $image = str_replace(' ', '+', $image);
                    $imageName = 'notary_'.time().'_'.Str::random(10).'.'.$extension;
                    $screenshotPath = 'debug-notary/'.$imageName;

                    Storage::disk('public')->put($screenshotPath, base64_decode($image));
                }
            }
        }

        $userContext = DebugNotary::resolveUserContext();

        $bug = RecordedBug::create([
            'log_type' => 'notary',
            'message' => __('debug-notary::messages.manual_log', ['note' => $note]),
            'user_note' => $note,
            'tags' => $tags,
            'url' => $url,
            'browser_data' => DebugNotary::maskData($browserData ?? []),
            'screenshot' => $screenshotBase64,
            'screenshot_path' => $screenshotPath,
            'severity' => 'info',
            'file' => 'browser',
            'line' => 0,
            'last_seen_at' => now(),
            'user_id' => $userContext['user_id'],
            'user_role' => $userContext['user_role'],
        ]);

        $bug->updateTrendData();
        $bug->save();

        $attachmentBase64 = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $attachmentOriginalName = $attachment->getClientOriginalName();
            $attachmentNameStore = 'attachment_'.time().'_'.Str::random(10).'.'.$attachment->getClientOriginalExtension();
            $attachmentPath = 'debug-notary/attachments/'.$attachmentNameStore;

            $attachmentContent = file_get_contents($attachment);
            Storage::disk('local')->put($attachmentPath, $attachmentContent);

            $attachmentBase64 = base64_encode($attachmentContent);
            $attachmentName = $attachmentOriginalName;

            $bug->messages()->create([
                'user_id' => $userContext['user_id'],
                'message' => __('debug-notary::messages.attachment_added', ['name' => $attachmentOriginalName]),
                'attachment_path' => $attachmentPath,
                'attachment_type' => $attachment->getClientMimeType(),
            ]);
        }

        // Send to central
        $centralId = DebugNotary::sendToCentral([
            'log_type' => 'manual',
            'message' => $bug->message,
            'severity' => $bug->severity,
            'file' => $bug->file,
            'line' => $bug->line,
            'url' => $bug->url,
            'screenshot' => $bug->screenshot,
            'attachment' => $attachmentBase64,
            'attachment_name' => $attachmentName,
            'browser_data' => $bug->browser_data,
            'user_context' => $userContext,
            'note' => $note,
            'tags' => $tags,
        ]);

        if ($centralId) {
            $bug->central_id = $centralId;
            $bug->save();
        }

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, $id)
    {
        if ($gate = config('debug-notary.access_gate')) {
            Gate::authorize($gate);
        }

        $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved',
        ]);

        $bug = RecordedBug::findOrFail($id);
        $bug->status = $request->input('status');
        $bug->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('message', __('debug-notary::messages.status_updated'));
    }

    public function destroy($id)
    {
        if ($gate = config('debug-notary.access_gate')) {
            Gate::authorize($gate);
        }

        RecordedBug::findOrFail($id)->delete();

        return redirect()->back()->with('message', __('debug-notary::messages.bug_deleted'));
    }

    public function bulkDestroy(Request $request)
    {
        if ($gate = config('debug-notary.access_gate')) {
            Gate::authorize($gate);
        }

        if ($request->boolean('delete_all')) {
            $search = $request->input('search');
            $tag = $request->input('tag');

            $query = RecordedBug::query()
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('message', 'like', '%'.$search.'%')
                            ->orWhere('file', 'like', '%'.$search.'%')
                            ->orWhere('user_note', 'like', '%'.$search.'%');
                    });
                })
                ->when($tag, function ($query) use ($tag) {
                    $query->whereJsonContains('tags', $tag);
                });

            $count = $query->count();
            $query->delete();

            return redirect()->back()->with('message', __('debug-notary::messages.bugs_deleted', ['count' => $count]));
        }

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('message', __('debug-notary::messages.no_bugs_selected'));
        }

        RecordedBug::whereIn('id', $ids)->delete();

        return redirect()->back()->with('message', __('debug-notary::messages.bugs_deleted', ['count' => count($ids)]));
    }

    protected function authorizeSyncRequest(Request $request): void
    {
        $configuredKey = config('debug-notary.api_key') ?: config('debug-notary.central.api_key');

        if (! empty($configuredKey)) {
            $token = $request->bearerToken()
                ?? $request->header('X-Notary-Token')
                ?? $request->header('X-Api-Key');

            if (! $token || ! hash_equals((string) $configuredKey, (string) $token)) {
                abort(response()->json(['message' => 'Unauthorized'], 401));
            }
        }
    }

    public function getSyncUsers(Request $request)
    {
        $this->authorizeSyncRequest($request);

        $users = DebugNotary::resolveUsersForSync();

        return response()->json([
            'users' => $users,
        ]);
    }

    public function receiveSyncMessage(Request $request)
    {
        $this->authorizeSyncRequest($request);

        $data = $request->validate([
            'central_id' => 'required|integer',
            'message' => 'nullable|string',
            'user_name' => 'nullable|string',
            'attachment_path' => 'nullable|string',
            'attachment_type' => 'nullable|string',
            'attachment_name' => 'nullable|string',
            'attachment_data' => 'nullable|string',
            'attachment_base64' => 'nullable|string',
            'attachment' => 'nullable',
            'file' => 'nullable|file',
        ]);

        $bug = RecordedBug::where('central_id', $data['central_id'])->firstOrFail();

        $userName = $data['user_name'] ?? null;
        $attachmentPath = null;
        $attachmentType = $data['attachment_type'] ?? null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('debug-notary/attachments', 'local');
            $attachmentType = $attachmentType ?: ($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        } elseif ($request->hasFile('file')) {
            $file = $request->file('file');
            $attachmentPath = $file->store('debug-notary/attachments', 'local');
            $attachmentType = $attachmentType ?: ($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        } elseif (! empty($data['attachment_data']) || ! empty($data['attachment_base64']) || (isset($data['attachment']) && is_string($data['attachment']) && ! empty($data['attachment']))) {
            $rawBase64 = $data['attachment_data'] ?? $data['attachment_base64'] ?? $data['attachment'];

            if (str_contains($rawBase64, 'base64,')) {
                $rawBase64 = explode('base64,', $rawBase64)[1];
            }

            $decoded = base64_decode($rawBase64, true);
            if ($decoded !== false) {
                $ext = $attachmentType;
                if (! $ext && ! empty($data['attachment_name'])) {
                    $ext = pathinfo($data['attachment_name'], PATHINFO_EXTENSION);
                }
                if (! $ext && ! empty($data['attachment_path'])) {
                    $ext = pathinfo($data['attachment_path'], PATHINFO_EXTENSION);
                }
                $ext = $ext ? strtolower($ext) : 'bin';

                $filename = 'debug-notary/attachments/'.Str::random(40).'.'.$ext;
                Storage::disk('local')->put($filename, $decoded);
                $attachmentPath = $filename;
                $attachmentType = $ext;
            }
        } elseif (! empty($data['attachment_path'])) {
            $attachmentPath = $data['attachment_path'];
            $attachmentType = $attachmentType ?: pathinfo($attachmentPath, PATHINFO_EXTENSION);
        }

        $bug->messages()->create([
            'user_id' => null, // Fra central
            'message' => ($userName ? $userName.': ' : '').($data['message'] ?? ''),
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        return response()->json(['success' => true]);
    }

    public function messageAttachment(Request $request, $bugId, $messageId)
    {
        if ($gate = config('debug-notary.access_gate')) {
            Gate::authorize($gate);
        }

        $bug = RecordedBug::findOrFail($bugId);
        $message = $bug->messages()->findOrFail($messageId);

        if (! $message->attachment_path) {
            abort(404, 'Attachment not found');
        }

        $disk = Storage::disk('local')->exists($message->attachment_path)
            ? 'local'
            : (Storage::disk('public')->exists($message->attachment_path) ? 'public' : null);

        if (! $disk) {
            abort(404, 'Attachment file not found');
        }

        if ($request->boolean('download')) {
            return Storage::disk($disk)->download($message->attachment_path, basename($message->attachment_path));
        }

        return Storage::disk($disk)->response($message->attachment_path);
    }

    public function receiveSyncBug(Request $request)
    {
        $this->authorizeSyncRequest($request);

        $data = $request->validate([
            'central_id' => 'required|integer',
            'status' => 'nullable|string',
            'assigned_to_email' => 'nullable|string',
            'assigned_to_name' => 'nullable|string',
            'assigned_to_type' => 'nullable|string',
            'external_user_id' => 'nullable|string',
            'user_name' => 'nullable|string',
            'external_user_name' => 'nullable|string',
            'estimate_hours' => 'nullable|integer|min:0',
            'estimate_minutes' => 'nullable|integer|min:0|max:59',
            'estimate_accepted' => 'nullable|boolean',
            'estimate_accepted_at' => 'nullable|date',
            'estimate_accepted_by_name' => 'nullable|string',
            'estimate_accepted_by_email' => 'nullable|string',
        ]);

        $bug = RecordedBug::where('central_id', $data['central_id'])->firstOrFail();

        $actorName = $data['external_user_name'] ?? $data['user_name'] ?? $data['assigned_to_name'] ?? 'DebugCentral';

        if (isset($data['status'])) {
            $currentStatusVal = $bug->status instanceof \UnitEnum ? $bug->status->value : $bug->status;
            if ($currentStatusVal !== $data['status']) {
                $statusLabels = [
                    'open' => 'Åben',
                    'in_progress' => 'I gang',
                    'pending' => 'Venter',
                    'resolved' => 'Løst',
                    'wont_fix' => 'Løses ikke',
                ];
                $statusLabel = $statusLabels[$data['status']] ?? $data['status'];
                $bug->status = $data['status'];
                $bug->messages()->create([
                    'user_id' => null,
                    'message' => "Status ændret til '{$statusLabel}' af {$actorName}",
                ]);
            }
        }

        if (array_key_exists('assigned_to_email', $data)) {
            $oldAssigneeId = $bug->assigned_to_id;
            $newAssignee = null;
            if ($data['assigned_to_email']) {
                $userModel = config('debug-notary.user_model')
                    ?: config('auth.providers.users.model')
                    ?: User::class;

                if (! empty($data['external_user_id']) && class_exists($userModel)) {
                    $newAssignee = $userModel::find($data['external_user_id']);
                }
                if (! $newAssignee && ! empty($data['assigned_to_email']) && class_exists($userModel)) {
                    $newAssignee = $userModel::where('email', $data['assigned_to_email'])->first();
                }
                $bug->assigned_to_id = $newAssignee ? $newAssignee->id : null;
            } else {
                $bug->assigned_to_id = null;
            }

            if ($oldAssigneeId !== $bug->assigned_to_id) {
                $assigneeName = $newAssignee?->name ?? $data['assigned_to_name'] ?? null;
                if ($assigneeName) {
                    $bug->messages()->create([
                        'user_id' => null,
                        'message' => "Tildelt til {$assigneeName} af {$actorName}",
                    ]);
                } else {
                    $bug->messages()->create([
                        'user_id' => null,
                        'message' => "Tildeling fjernet af {$actorName}",
                    ]);
                }
            }
        }

        // Handle estimate hours and minutes if estimate is not yet accepted (acceptance is binding)
        if ((array_key_exists('estimate_hours', $data) || array_key_exists('estimate_minutes', $data)) && ! $bug->isEstimateAccepted()) {
            $newHours = array_key_exists('estimate_hours', $data) ? ($data['estimate_hours'] !== null && $data['estimate_hours'] !== '' ? (int) $data['estimate_hours'] : null) : $bug->estimate_hours;
            $newMinutes = array_key_exists('estimate_minutes', $data) ? ($data['estimate_minutes'] !== null && $data['estimate_minutes'] !== '' ? (int) $data['estimate_minutes'] : null) : $bug->estimate_minutes;

            if ($newHours !== $bug->estimate_hours || $newMinutes !== $bug->estimate_minutes) {
                $bug->estimate_hours = $newHours;
                $bug->estimate_minutes = $newMinutes;

                $formatted = $bug->formattedEstimate();
                if ($formatted) {
                    $bug->messages()->create([
                        'user_id' => null,
                        'message' => "Estimat sat til {$formatted} af {$actorName}",
                    ]);
                } else {
                    $bug->messages()->create([
                        'user_id' => null,
                        'message' => "Estimat fjernet af {$actorName}",
                    ]);
                }
            }
        }

        // Handle estimate acceptance
        $estimateAccepted = isset($data['estimate_accepted']) ? filter_var($data['estimate_accepted'], FILTER_VALIDATE_BOOLEAN) : false;
        if (($estimateAccepted || ! empty($data['estimate_accepted_at'])) && ! $bug->isEstimateAccepted()) {
            $acceptedAt = ! empty($data['estimate_accepted_at']) ? Carbon::parse($data['estimate_accepted_at']) : now();
            $acceptedByName = $data['estimate_accepted_by_name'] ?? $actorName;

            $userModel = config('debug-notary.user_model')
                ?: config('auth.providers.users.model')
                ?: User::class;

            $acceptedUser = null;
            if (! empty($data['external_user_id']) && class_exists($userModel)) {
                $acceptedUser = $userModel::find($data['external_user_id']);
            }
            if (! $acceptedUser && ! empty($data['estimate_accepted_by_email']) && class_exists($userModel)) {
                $acceptedUser = $userModel::where('email', $data['estimate_accepted_by_email'])->first();
            }

            $bug->estimate_accepted_at = $acceptedAt;
            $bug->estimate_accepted_by_id = $acceptedUser?->id;
            $bug->estimate_accepted_by_name = $acceptedByName;

            $formatted = $bug->formattedEstimate() ?: '0 timer 0 minutter';
            $dateStr = $acceptedAt->format('d/m/Y H:i');

            $bug->messages()->create([
                'user_id' => null,
                'message' => "Estimat ({$formatted}) godkendt bindende af {$acceptedByName} ({$dateStr})",
            ]);
        }

        $bug->save();

        return response()->json(['success' => true]);
    }
}
