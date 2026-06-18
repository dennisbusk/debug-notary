<?php

namespace Dennisbusk\DebugNotary\Models;

use Illuminate\Database\Eloquent\Model;

class RecordedBugMessage extends Model
{
    protected $fillable = ['recorded_bug_id', 'user_id', 'message', 'is_read', 'attachment_path', 'attachment_type'];

    public function bug()
    {
        return $this->belongsTo(RecordedBug::class, 'recorded_bug_id');
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
