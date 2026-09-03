<?php

namespace Dennisbusk\DebugNotary\Http\Controllers;

use App\Models\User;
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
            Storage::disk('public')->put($attachmentPath, $attachmentContent);

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
            'message' => 'required|string',
            'user_name' => 'nullable|string',
            'attachment_path' => 'nullable|string',
            'attachment_type' => 'nullable|string',
        ]);

        $bug = RecordedBug::where('central_id', $data['central_id'])->firstOrFail();

        $userName = $data['user_name'] ?? null;

        $bug->messages()->create([
            'user_id' => null, // Fra central
            'message' => ($userName ? $userName.': ' : '').$data['message'],
            'attachment_path' => $data['attachment_path'] ?? null,
            'attachment_type' => $data['attachment_type'] ?? null,
        ]);

        return response()->json(['success' => true]);
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
        ]);

        $bug = RecordedBug::where('central_id', $data['central_id'])->firstOrFail();

        if (isset($data['status'])) {
            $bug->status = $data['status'];
        }

        if (array_key_exists('assigned_to_email', $data)) {
            if ($data['assigned_to_email']) {
                $userModel = config('debug-notary.user_model')
                    ?: config('auth.providers.users.model')
                    ?: User::class;

                $user = null;
                if (! empty($data['external_user_id']) && class_exists($userModel)) {
                    $user = $userModel::find($data['external_user_id']);
                }
                if (! $user && ! empty($data['assigned_to_email']) && class_exists($userModel)) {
                    $user = $userModel::where('email', $data['assigned_to_email'])->first();
                }
                $bug->assigned_to_id = $user ? $user->id : null;
            } else {
                $bug->assigned_to_id = null;
            }
        }

        $bug->save();

        return response()->json(['success' => true]);
    }
}
