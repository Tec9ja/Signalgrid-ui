<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FloorPlanFile extends Model
{
    public $timestamps = false;

    protected $fillable = ['floor_plan_id', 'contents'];

    protected $hidden = ['contents'];
}
