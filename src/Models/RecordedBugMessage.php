<?php

namespace Dennisbusk\DebugNotary\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class RecordedBugMessage extends Model {

    protected $fillable = [ 'recorded_bug_id', 'user_id', 'message', 'is_read', 'attachment_path', 'attachment_type' ];

    protected function userId(): Attribute {
        return Attribute::make(
            set: fn( $value ) => ( $value === '' || $value === null ) ? null : (int) $value,
        );
    }

    public function bug() {
        return $this->belongsTo(RecordedBug::class, 'recorded_bug_id');
    }

    public function user() {
        $userModel = config('debug-notary.user_model')
            ?: config('auth.providers.users.model')
                ?: User::class;

        return $this->belongsTo($userModel);
    }
}
