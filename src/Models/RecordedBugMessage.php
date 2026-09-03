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
        $userModel = config('debug-notary.user_model')
            ?: config('auth.providers.users.model')
            ?: \App\Models\User::class;

        return $this->belongsTo($userModel);
    }
}
