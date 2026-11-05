<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemCheck extends Model
{
    protected $fillable = ['check_key', 'status', 'message', 'details', 'checked_at'];
    protected function casts(): array { return ['details' => 'array', 'checked_at' => 'datetime']; }
}
