<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'event_key', 'database_enabled', 'mail_enabled'];
    protected function casts(): array { return ['database_enabled' => 'boolean', 'mail_enabled' => 'boolean']; }
    public function user() { return $this->belongsTo(User::class); }
}
