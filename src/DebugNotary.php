<?php

namespace Dennisbusk\DebugNotary;

use Dennisbusk\DebugNotary\Http\Controllers\DebugNotaryController;
use Dennisbusk\DebugNotary\Jobs\NotifyBugJob;
use Dennisbusk\DebugNotary\Mail\BugRecordedMail;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http as LaravelHttp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

class DebugNotary
{
    /**
     * Track if routes have been registered manually.
     */
    public static bool $routesRegistered = false;

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
        Route::post('laravel-debug-notary/store', [DebugNotaryController::class, 'storeNotary'])->name('debug-notary.store');
    }

    /**
     * Register the management routes only.
     */
    public static function managementRoutes(): void
    {
        Route::get('laravel-debug-notary', [DebugNotaryController::class, 'index'])->name('debug-notary.index');
        Route::patch('laravel-debug-notary/{id}/status', [DebugNotaryController::class, 'updateStatus'])->name('debug-notary.update-status');
        Route::delete('laravel-debug-notary/{id}', [DebugNotaryController::class, 'destroy'])->name('debug-notary.destroy');
        Route::post('laravel-debug-notary/bulk-delete', [DebugNotaryController::class, 'bulkDestroy'])->name('debug-notary.bulk-destroy');
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
        $context = [
            'user_id' => Auth::id(),
            'user_role' => null,
        ];

        if (Auth::check()) {
            $user = Auth::user();
            if (isset($user->role)) {
                $context['user_role'] = (string) $user->role;
            } elseif (method_exists($user, 'getRoleNames')) {
                $context['user_role'] = $user->getRoleNames()->first();
            }
        }

        return $context;
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

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $backtrace[2] ?? ($backtrace[1] ?? null);

            $file = $caller['file'] ?? 'unknown';
            $line = $caller['line'] ?? 0;
            $hash = md5($message.$file.$line);

            $bug = RecordedBug::firstOrNew(['hash' => $hash]);
            $isNew = ! $bug->exists;

            if ($isNew) {
                $bug->message = $message;
                $bug->file = $file;
                $bug->line = $line;
                $bug->log_type = 'notary';
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
            }
        } catch (\Throwable $e) {
            // Silently fail to avoid breaking the application
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
