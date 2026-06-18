<?php

namespace Dennisbusk\DebugNotary\Models;

/** @noinspection PhpUndefinedClassInspection */

use Dennisbusk\DebugNotary\Enums\BugSeverity;
use Dennisbusk\DebugNotary\Enums\BugStatus;
use Dennisbusk\DebugNotary\Facades\DebugNotary;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Schema;

class RecordedBug extends Model
{
    use Prunable;

    /**
     * Levels and their weights (higher is more important)
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

    public const STATUS_PENDING = 'pending';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_WONT_FIX = 'wont_fix';

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
            'assigned_to_id',
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
            'assigned_to_id' => 'integer',
            'status' => BugStatus::class,
        ];

    protected $attributes
        = [
            'status' => self::STATUS_OPEN,
        ];

    public function assignedTo()
    {
        $userModel = config('auth.providers.users.model');

        return $this->belongsTo($userModel, 'assigned_to_id');
    }

    public function user()
    {
        // We assume Auth::user() returns a model.
        // In a package, it's hard to know exactly which class it is,
        // so we use config or standard Laravel setup.
        $userModel = config('auth.providers.users.model');

        return $this->belongsTo($userModel);
    }

    public function messages()
    {
        return $this->hasMany(RecordedBugMessage::class, 'recorded_bug_id');
    }

    /**
     * Get the prunable query.
     */
    public function prunable()
    {
        $config = config('debug-notary.prune_days');

        if (is_numeric($config)) {
            return static::where('last_seen_at', '<=', now()->subDays($config));
        }

        if (is_array($config)) {
            return static::where(function ($query) use ($config) {
                foreach ($config as $type => $days) {
                    $query->orWhere(function ($q) use ($type, $days) {
                        $q->where('log_type', $type)
                            ->where('last_seen_at', '<=', now()->subDays($days));
                    });
                }
            });
        }

        return static::where('last_seen_at', '<=', now()->subDays(30));
    }

    public static function generateHash($message, $file, $line): string
    {
        // Normaliser besked ved at fjerne ID'er og UUID'er for bedre gruppering
        $normalized = preg_replace('/\d+/', '{ID}', $message);
        $normalized = preg_replace('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', '{UUID}', $normalized);

        // Brugerdefinerede mønstre fra konfiguration
        $customPatterns = config('debug-notary.normalization_patterns', []);
        foreach ($customPatterns as $pattern => $replacement) {
            $normalized = preg_replace($pattern, $replacement, $normalized);
        }

        return md5($normalized.$file.$line);
    }

    public static function record(\Throwable $e): self
    {
        try {
            $message = $e->getMessage() ?: 'No message';
            $file = $e->getFile() ?: 'unknown';
            $line = $e->getLine() ?: 0;
            $hash = static::generateHash($message, $file, $line);

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
        $newSeverityValue = match (true) {
            $this->count >= 100 => BugSeverity::CRITICAL,
            $this->count >= 50 => BugSeverity::HIGH,
            $this->count >= 10 => BugSeverity::MEDIUM,
            default => $this->severity instanceof BugSeverity ? $this->severity : BugSeverity::LOW,
        };

        // Hvis vi allerede har en severity (f.eks. fra enum cast), så sammenlign vægte
        $currentWeight = $this->severity instanceof BugSeverity ? $this->severity->weight() : (self::LEVELS[$this->severity] ?? 0);
        $newWeight = $newSeverityValue->weight();

        if ($newWeight > $currentWeight) {
            $this->severity = $newSeverityValue; // Mutator ensures storage as string value
        }
    }

    protected function severity(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Return enum when valid, otherwise keep original string (e.g., 'error')
                try {
                    return BugSeverity::tryFrom((string) $value) ?? $value;
                } catch (\Throwable $e) {
                    return $value;
                }
            },
            set: function ($value) {
                if ($value instanceof BugSeverity) {
                    return $value->value;
                }

                return (string) $value;
            }
        );
    }
}
