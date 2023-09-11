<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'logs';
    protected $fillable = [
        'description',
        'origin',
        'type',
        'result',
        'level',
        'token',
        'ip',
        'user_agent',
        'updated_at'
    ];
}
