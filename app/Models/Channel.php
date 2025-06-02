<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
   protected $table = 'channels';
   protected $guarded = [];
   protected $casts = [
      'channel_videos' => 'array',
      'videos' => 'array',
      'logs' => 'array',
      'short_videos' => 'array',
   ];
}
