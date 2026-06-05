<?php

namespace Dennisbusk\DebugNotary\Models;

/** @noinspection PhpUndefinedClassInspection */

use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Schema;

class RecordedBug extends Model
{
    use Prunable;

    /**
     * Niveauer og deres vægt (jo højere, jo vigtigere)
     */
    public const LEVELS
        = [
            'debug' => 0,
            'info' => 1,
            'notice' => 2,
            'low' => 2,
            'warning' => 3,
            'medium' => 3,
            'error' => 4,
            'high' => 4,
            'critical' => 5,
            'alert' => 6,
            'emergency' => 7,
        ];

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable
        = [
            'hash',
            'log_type',
            'message',
            'file',
            'line',
            'severity',
            'status',
            'count',
            'trend_data',
            'stack_trace',
            'screenshot',
            'screenshot_path',
            'url',
            'browser_data',
            'user_note',
            'tags',
            'user_id',
            'user_role',
            'tenant_id',
            'last_seen_at',
        ];

    protected $casts
        = [
            'last_seen_at' => 'datetime',
            'count' => 'integer',
            'line' => 'integer',
            'browser_data' => 'json',
            'trend_data' => 'json',
            'tags' => 'json',
            'user_id' => 'integer',
        ];

    protected $attributes
        = [
            'status' => self::STATUS_OPEN,
        ];

    public function user()
    {
        // Vi antager at Auth::user() returnerer en model.
        // I en pakke er det svært at vide præcis hvilken klasse det er,
        // så vi bruger config eller standard Laravel setup.
        $userModel = config('auth.providers.users.model');

        return $this->belongsTo($userModel);
    }

    /**
     * Get the prunable query.
     */
    public function prunable()
    {
        $days = config('debug-notary.prune_days', 30);

        return static::where('last_seen_at', '<=', now()->subDays($days));
    }

    public static function record(\Throwable $e): self
    {
        try {
            $message = $e->getMessage() ?: 'No message';
            $file = $e->getFile() ?: 'unknown';
            $line = $e->getLine() ?: 0;
            $hash = md5($message.$file.$line);

            $userContext = DebugNotary::resolveUserContext();

            $bug = static::where('hash', $hash)->first();

            if ($bug) {
                $bug->increment('count');
                $bug->updateSeverity();
                $bug->updateTrendData();
                $bug->last_seen_at = now();
                $bug->user_id = $userContext['user_id'];
                $bug->user_role = $userContext['user_role'];
                $bug->save();

                return $bug;
            }

            $bug = static::create([
                'hash' => $hash,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'stack_trace' => $e->getTraceAsString(),
                'user_id' => $userContext['user_id'],
                'user_role' => $userContext['user_role'],
                'last_seen_at' => now(),
                'severity' => 'low',
                'log_type' => 'system',
            ]);

            $bug->updateTrendData();
            $bug->save();

            return $bug;
        } catch (\Throwable $ex) {
            // Return a new instance that isn't saved to avoid crashing
            return new static;
        }
    }

    public function updateTrendData(): void
    {
        // Check if the column exists to avoid errors during migrations
        static $hasColumn = null;
        if ($hasColumn === null) {
            try {
                $hasColumn = Schema::hasColumn($this->getTable(), 'trend_data');
            } catch (\Throwable $e) {
                $hasColumn = false;
            }
        }

        if (! $hasColumn) {
            return;
        }

        $trend = is_array($this->trend_data) ? $this->trend_data : [];
        $today = now()->format('Y-m-d');

        if (! isset($trend[$today])) {
            $trend[$today] = 0;
        }

        $trend[$today]++;

        // Behold kun de sidste 7 dage (vi kan gemme lidt flere for at være sikre, men vi viser 7)
        $keys = array_keys($trend);
        sort($keys);
        if (count($keys) > 10) {
            $keysToRemove = array_slice($keys, 0, count($keys) - 10);
            foreach ($keysToRemove as $key) {
                unset($trend[$key]);
            }
        }

        $this->trend_data = $trend;
    }

    public function updateSeverity(): void
    {
        $newSeverity = match (true) {
            $this->count >= 100 => 'critical',
            $this->count >= 50 => 'high',
            $this->count >= 10 => 'medium',
            default => $this->severity ?? 'low',
        };

        $currentWeight = self::LEVELS[$this->severity] ?? 0;
        $newWeight = self::LEVELS[$newSeverity] ?? 0;

        if ($newWeight > $currentWeight) {
            $this->severity = $newSeverity;
        }
    }
}
