<?php

namespace Dennisbusk\DebugNotary\Listeners;

use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Dennisbusk\DebugNotary\Models\RecordedBug;
use Illuminate\Log\Events\MessageLogged;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class LogMessageListener
{
    public function handle(MessageLogged $event): void
    {
        try {
            if (! config('debug-notary.enabled')) {
                return;
            }

            // Ignorer MethodNotAllowedHttpException for Livewire update ruter ved GET requests
            // Dette sker ofte pga. bots eller prefetchers der rammer Livewire endpoints
            if (isset($event->context['exception'])
                && $event->context['exception'] instanceof MethodNotAllowedHttpException) {
                $path = request()->getPathInfo();
                if (str_contains($path, '/update') && (str_contains($path, 'livewire') || request()->hasHeader('X-Livewire'))) {
                    return;
                }
            }

            $level = $event->level;
            $configLevel = config('debug-notary.debug_level', 'error');

            // Tjek om niveauet er højt nok
            if ((RecordedBug::LEVELS[$level] ?? 0) < (RecordedBug::LEVELS[$configLevel] ?? 4)) {
                return;
            }

            // Tjek om det er en system log eller en notary log
            $isNotaryLog = isset($event->context['notary']) && $event->context['notary'] === true;

            if ($isNotaryLog && ! config('debug-notary.notary_log', true)) {
                return;
            }

            if (! $isNotaryLog && ! config('debug-notary.system_log', true)) {
                return;
            }

            $this->recordBug($event);
        } catch (Throwable $e) {
            // Silently fail to avoid breaking the application when bug recording fails
            // This also prevents infinite logging loops
        }
    }

    protected function recordBug(MessageLogged $event): void
    {
        $message = $event->message;
        $context = $event->context;

        $file = 'unknown';
        $line = 0;
        $stackTrace = null;

        // Prøv at udtrække fil og linje fra exception i context
        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $e = $context['exception'];
            $file = $e->getFile();
            $line = $e->getLine();
            $stackTrace = $e->getTraceAsString();
        }

        $hash = md5($message.$file.$line);

        // Find eksisterende bug for at tælle op, eller opret ny
        $bug = RecordedBug::firstOrNew([
            'hash' => $hash,
        ]);
        $isNew = ! $bug->exists;

        if ($isNew) {
            $bug->message = $message;
            $bug->file = $file;
            $bug->line = $line;
            $bug->log_type = (isset($context['notary']) && $context['notary'] === true) ? 'notary' : 'system';
        }

        $bug->severity = $event->level;
        $bug->count += 1;
        $bug->last_seen_at = now();

        if ($stackTrace) {
            $bug->stack_trace = $stackTrace;
        }

        // Gem fuld context i browser_data
        $bug->browser_data = DebugNotary::maskData($context);

        // Brug centraliseret bruger-context logik
        $userContext = DebugNotary::resolveUserContext();
        $bug->user_id = $userContext['user_id'];
        $bug->user_role = $userContext['user_role'];

        // Understøttelse af tenant_id hvis det findes i context eller config
        if (isset($context['tenant_id'])) {
            $bug->tenant_id = (string) $context['tenant_id'];
        }

        $bug->updateTrendData();
        $bug->updateSeverity();
        $bug->save();

        if ($isNew) {
            DebugNotary::notifyNewBug($bug);
        }
    }
}
