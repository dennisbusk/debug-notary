<?php

namespace Dennisbusk\DebugNotary;

use App\Models\User;
use Dennisbusk\DebugNotary\Http\Controllers\DebugNotaryController;
use Dennisbusk\DebugNotary\Jobs\NotifyBugJob;
use Dennisbusk\DebugNotary\Mail\BugRecordedMail;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Dennisbusk\DebugNotary\Models\RecordedBugMessage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http as LaravelHttp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

class DebugNotary
{
    /**
     * Track if routes have been registered manually.
     */
    public static bool $routesRegistered = false;

    /**
     * Custom context resolver.
     */
    public static $userContextResolver = null;

    /**
     * Custom users resolver for sync.
     */
    public static $usersResolver = null;

    /**
     * Set a custom user context resolver.
     */
    public static function resolveUserContextUsing(callable $callback): void
    {
        static::$userContextResolver = $callback;
    }

    /**
     * Set a custom users resolver for syncing users with DebugCentral.
     */
    public static function resolveUsersUsing(callable $callback): void
    {
        static::$usersResolver = $callback;
    }

    /**
     * Register the package routes.
     */
    public static function routes(): void
    {
        if (static::$routesRegistered) {
            return;
        }

        static::$routesRegistered = true;
        Route::group([], __DIR__.'/routes.php');
    }

    /**
     * Register the reporting routes only.
     */
    public static function reportingRoutes(): void
    {
        $prefix = config('debug-notary.route_prefix', 'laravel-debug-notary');
        Route::post($prefix.'/store', [DebugNotaryController::class, 'storeNotary'])->name('debug-notary.store');
    }

    /**
     * Register the management routes only.
     */
    public static function managementRoutes(): void
    {
        $prefix = config('debug-notary.route_prefix', 'laravel-debug-notary');
        Route::get($prefix, [DebugNotaryController::class, 'index'])->name('debug-notary.index');
        Route::get($prefix.'/{id}', [DebugNotaryController::class, 'show'])->name('debug-notary.show');
        Route::patch($prefix.'/{id}/status', [DebugNotaryController::class, 'updateStatus'])->name('debug-notary.update-status');
        Route::delete($prefix.'/{id}', [DebugNotaryController::class, 'destroy'])->name('debug-notary.destroy');
        Route::post($prefix.'/bulk-delete', [DebugNotaryController::class, 'bulkDestroy'])->name('debug-notary.bulk-destroy');
    }

    /**
     * Register the synchronization routes.
     */
    public static function syncRoutes(): void
    {
        $prefix = config('debug-notary.route_prefix', 'laravel-debug-notary');
        Route::withoutMiddleware([ValidateCsrfToken::class, VerifyCsrfToken::class, 'web'])
            ->middleware('api')
            ->prefix($prefix.'/sync')
            ->group(function () {
                Route::get('/users', [DebugNotaryController::class, 'getSyncUsers']);
                Route::post('/messages', [DebugNotaryController::class, 'receiveSyncMessage']);
                Route::patch('/bugs', [DebugNotaryController::class, 'receiveSyncBug']);
            });
    }

    /**
     * Get the unread message count for the current user.
     */
    public static function getUnreadCountForUser(): int
    {
        if (! auth()->check()) {
            return 0;
        }

        return RecordedBugMessage::whereHas('bug', function ($q) {
            $q->where('user_id', auth()->id());
        })
            ->where('user_id', '!=', auth()->id())
            ->where('is_read', false)
            ->count();
    }

    /**
     * Log an info message.
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log an error message.
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a warning message.
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log a critical message.
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Resolve the current user's ID and role.
     */
    public function resolveUserContext(): array
    {
        if (static::$userContextResolver) {
            $context = call_user_func(static::$userContextResolver);

            return [
                'user_id' => (string) ($context['user_id'] ?? Auth::id()),
                'user_role' => (string) ($context['user_role'] ?? null),
            ];
        }

        $context = [
            'user_id' => (string) Auth::id(),
            'user_role' => null,
        ];

        if (Auth::check()) {
            $user = Auth::user();
            if (isset($user->role)) {
                $context['user_role'] = (string) $user->role;
            } elseif (method_exists($user, 'getRoleNames')) {
                $context['user_role'] = (string) $user->getRoleNames()->first();
            }
        }

        return $context;
    }

    /**
     * Report a manual log to the system.
     */
    public function report(string $message, array $options = []): void
    {
        $severity = $options['severity'] ?? 'error';
        $context = $options['context'] ?? [];
        $logType = $options['log_type'] ?? 'manual';

        // Prepare central payload
        $payload = [
            'log_type' => $logType,
            'message' => $message,
            'severity' => $severity,
            'file' => $options['file'] ?? 'unknown',
            'line' => $options['line'] ?? 0,
            'stack_trace' => $options['stack_trace'] ?? null,
            'url' => $options['url'] ?? request()->fullUrl(),
            'screenshot' => $options['screenshot'] ?? null,
            'attachment' => $options['attachment'] ?? null,
            'attachment_name' => $options['attachment_name'] ?? null,
            'browser_data' => $this->maskData($context),
            'user_context' => $options['user_context'] ?? $this->resolveUserContext(),
            'note' => $options['note'] ?? null,
            'tags' => $options['tags'] ?? [],
        ];

        // Send to central if enabled
        $centralId = $this->sendToCentral($payload);

        // Also log locally but tell it NOT to send to central again
        $this->log($severity, $message, array_merge($context, ['_no_central' => true, 'log_type' => $logType]));

        if ($centralId) {
            $hash = RecordedBug::generateHash($message, $payload['file'], $payload['line']);
            $bug = RecordedBug::where('hash', $hash)->first();
            if ($bug) {
                $bug->central_id = $centralId;
                $bug->save();
            }
        }
    }

    /**
     * Send a log payload to DebugCentral server.
     */
    public function sendToCentral(array $payload): ?int
    {
        if (! config('debug-notary.central.enabled', false)) {
            return null;
        }

        $url = config('debug-notary.central.api_url');
        $apiKey = config('debug-notary.central.api_key');

        if (! $url || ! $apiKey) {
            return null;
        }

        try {
            $response = LaravelHttp::withToken($apiKey)
                ->timeout(5)
                ->post($url, $this->maskData($payload));

            if ($response->successful()) {
                return $response->json('id');
            }
        } catch (\Exception $e) {
            // Silent fail to not break the application
            Log::error('DebugNotary Central Error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Sync a chat message to DebugCentral.
     */
    public function syncMessageToCentral(RecordedBug $bug, RecordedBugMessage $message): void
    {
        if (! $bug->central_id || ! config('debug-notary.central.enabled', false)) {
            return;
        }

        $url = config('debug-notary.central.api_url');
        $apiKey = config('debug-notary.central.api_key');

        if (! $url || ! $apiKey) {
            return;
        }

        $baseUrl = str_replace('/logs', '', $url);
        $messageUrl = rtrim($baseUrl, '/')."/logs/{$bug->central_id}/messages";

        try {
            LaravelHttp::withToken($apiKey)
                ->post($messageUrl, [
                    'message' => $message->message,
                    'external_user_id' => (string) $message->user_id,
                    'external_user_name' => $message->user->name ?? 'System',
                    'attachment_path' => $message->attachment_path,
                    'attachment_type' => $message->attachment_type,
                ]);
        } catch (\Exception $e) {
            Log::error('DebugNotary Central Message Sync Error: '.$e->getMessage());
        }
    }

    /**
     * Sync bug status/assignment to DebugCentral.
     */
    public function syncBugUpdateToCentral(RecordedBug $bug): void
    {
        if (! $bug->central_id || ! config('debug-notary.central.enabled', false)) {
            return;
        }

        $url = config('debug-notary.central.api_url');
        $apiKey = config('debug-notary.central.api_key');

        if (! $url || ! $apiKey) {
            return;
        }

        $baseUrl = str_replace('/logs', '', $url);
        $updateUrl = rtrim($baseUrl, '/')."/logs/{$bug->central_id}";

        try {
            $assignedUser = $bug->assignedTo;

            LaravelHttp::withToken($apiKey)
                ->patch($updateUrl, [
                    'status' => $bug->status instanceof \UnitEnum ? $bug->status->value : $bug->status,
                    'assigned_to_email' => $assignedUser?->email,
                    'assigned_to_name' => $assignedUser?->name,
                    'assigned_to_type' => 'site',
                    'external_user_id' => $assignedUser ? (string) $assignedUser->getKey() : null,
                ]);
        } catch (\Exception $e) {
            Log::error('DebugNotary Central Bug Update Sync Error: '.$e->getMessage());
        }
    }

    /**
     * Resolve users for synchronization with DebugCentral.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolveUsersForSync(): array
    {
        if (static::$usersResolver) {
            return call_user_func(static::$usersResolver);
        }

        $userModel = config('debug-notary.user_model')
            ?: config('auth.providers.users.model')
            ?: User::class;

        if (! class_exists($userModel)) {
            return [];
        }

        return $userModel::all()->map(function ($user) {
            $role = null;
            if (isset($user->role)) {
                $role = (string) $user->role;
            } elseif (isset($user->user_role)) {
                $role = (string) $user->user_role;
            } elseif (method_exists($user, 'getRoleNames')) {
                $role = (string) $user->getRoleNames()->first();
            } elseif (method_exists($user, 'roles') && $user->relationLoaded('roles')) {
                $role = $user->roles->first()?->name;
            }

            return [
                'id' => (string) $user->getKey(),
                'name' => (string) ($user->name ?? $user->email ?? "User #{$user->getKey()}"),
                'email' => (string) ($user->email ?? ''),
                'role' => $role,
            ];
        })->values()->all();
    }

    /**
     * Push application users to DebugCentral.
     *
     * @param  array<int, array<string, mixed>>|null  $users
     */
    public function syncUsersToCentral(?array $users = null): int
    {
        if (! config('debug-notary.central.enabled', false)) {
            throw new \RuntimeException('DebugCentral integration is not enabled in debug-notary config.');
        }

        $url = config('debug-notary.central.api_url');
        $apiKey = config('debug-notary.central.api_key');

        if (! $url || ! $apiKey) {
            throw new \RuntimeException('DebugCentral API URL or API Key is missing in debug-notary config.');
        }

        $users = $users ?? $this->resolveUsersForSync();

        $baseUrl = str_replace('/logs', '', $url);
        $syncUrl = rtrim($baseUrl, '/').'/sites/users';

        try {
            $response = LaravelHttp::withToken($apiKey)
                ->acceptJson()
                ->timeout(15)
                ->post($syncUrl, [
                    'users' => $users,
                ]);

            if (! $response->successful()) {
                Log::error('DebugNotary Sync Users to Central Error: HTTP '.$response->status().' '.$response->body());
                throw new \RuntimeException('Failed to sync users to DebugCentral: HTTP '.$response->status());
            }

            return (int) ($response->json('count') ?? count($users));
        } catch (\Exception $e) {
            Log::error('DebugNotary Central Sync Users Error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Internal log method.
     */
    protected function log(string $severity, string $message, array $context = []): void
    {
        try {
            if (! config('debug-notary.enabled')) {
                return;
            }

            // Check log level
            $minLevel = config('debug-notary.debug_level', 'error');
            $levels = RecordedBug::LEVELS;
            if (($levels[$severity] ?? 0) < ($levels[$minLevel] ?? 0)) {
                return;
            }

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $backtrace[2] ?? ($backtrace[1] ?? null);

            $file = $caller['file'] ?? 'unknown';
            $line = $caller['line'] ?? 0;
            $hash = RecordedBug::generateHash($message, $file, $line);

            $bug = RecordedBug::firstOrNew(['hash' => $hash]);
            $isNew = ! $bug->exists;

            if ($isNew) {
                $bug->message = $message;
                $bug->file = $file;
                $bug->line = $line;
                $bug->log_type = $context['log_type'] ?? 'system';
            }

            $userContext = $this->resolveUserContext();

            $bug->severity = $severity;
            $bug->url = request()->fullUrl();
            $bug->last_seen_at = now();
            $bug->count += 1;
            $bug->user_id = $userContext['user_id'];
            $bug->user_role = $userContext['user_role'];

            $bug->browser_data = $this->maskData($context);
            $bug->updateTrendData();
            $bug->updateSeverity();
            $bug->save();

            if ($isNew) {
                $this->notifyNewBug($bug);

                // Send to central if it's a new unique bug and not already sent
                if (! isset($context['_no_central'])) {
                    $centralId = $this->sendToCentral([
                        'log_type' => $context['log_type'] ?? 'system',
                        'message' => $message,
                        'severity' => $severity,
                        'file' => $file,
                        'line' => $line,
                        'url' => request()->fullUrl(),
                        'browser_data' => $bug->browser_data,
                        'user_context' => $userContext,
                        'tags' => [],
                    ]);

                    if ($centralId) {
                        $bug->central_id = $centralId;
                        $bug->save();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log the error to default Laravel log to help debugging the package itself
            Log::error('DebugNotary internal error: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Notify about a new unique bug.
     */
    public function notifyNewBug(RecordedBug $bug): void
    {
        if (! config('debug-notary.notifications.enabled')) {
            return;
        }

        // Rate limiting
        $rateLimit = config('debug-notary.notifications.rate_limit', 60);
        if ($rateLimit > 0) {
            $cacheKey = 'debug-notary-notified-'.$bug->hash;
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, true, now()->addMinutes($rateLimit));
        }

        if (config('debug-notary.notifications.queue')) {
            NotifyBugJob::dispatch($bug);

            return;
        }

        $message = "New Bug Recorded: {$bug->message} in {$bug->file}:{$bug->line}";

        // Slack
        if ($webhook = config('debug-notary.notifications.slack_webhook')) {
            try {
                LaravelHttp::post($webhook, [
                    'text' => $message,
                ]);
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        // Mail
        if ($email = config('debug-notary.notifications.mail_to')) {
            try {
                Mail::to($email)->send(new BugRecordedMail($bug));
            } catch (\Exception $e) {
                // Silent fail
            }
        }
    }

    /**
     * Mask sensitive data in an array.
     */
    public function maskData(array $data): array
    {
        if (! config('debug-notary.masking.enabled', true)) {
            return $data;
        }

        $maskFields = config('debug-notary.masking.fields', []);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskData($value);
            } elseif (in_array(strtolower((string) $key), $maskFields)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }
}
