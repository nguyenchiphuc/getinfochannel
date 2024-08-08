<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Log;
use App\Models\Channel;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\Setting;

class HomeController extends Controller
{
  public function index(Request $request) {
    $setting = Setting::where('id', 1)->first();
    if(!$setting) {
      $setting = Setting::create(['token' => 'tuandm']);
    }
    if($request->token == $setting->token) {
      return view('proxy', ['info' => $setting]);
    }
    return view('welcome');
  }

  public function proxy(Request $request) {
    Setting::where('id', 1)->update(['proxys' => $request->proxys,'token' => $request->token, 'using_proxy' => $request->using_proxy]);
    return redirect()->back(); 
  }
}
