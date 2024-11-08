<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Proxy;
class Proxy extends Model
{
   protected $table = 'proxies';
   protected $guarded = [];
   public static function getProxy() {
      return Proxy::where('live', 1)->orderby( 'count_used' )->first();
   }
}
