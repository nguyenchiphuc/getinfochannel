<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Log;
use App\Models\Channel;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use App\Models\Proxy;
use GuzzleHttp\Client;
use App\Models\Setting;

class HomeController extends Controller
{
  protected $proxy = '';
  protected $setting;
  
  public function __construct() {
    $setting = Setting::where('id', 1)->first();
    $this->proxy = Proxy::getProxy();
    $this->setting = $setting;
  }
  public function randomProxy($proxy) {
    $proxyArr = explode('|', $proxy);
    $index = array_rand($proxyArr,1);
    return $proxyArr[$index];
  }
  public function index(Request $request) {
    // Artisan::call('get:proxy');
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
    Setting::where('id', 1)->update(['api_proxy_get' => $request->api_proxy_get,'token' => $request->token, 'using_proxy' => $request->using_proxy]);
    return redirect()->back(); 
  }

  public function channel(Request $request, $string) {
    $url = "https://www.youtube.com/channel/".$string;
    $headers = [
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'accept-language: vi,vi-VN;q=0.9,en-US;q=0.8,en;q=0.7',
        'priority: u=0, i',
        'sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: document',
        'sec-fetch-mode: navigate',
        'sec-fetch-site: none',
        'sec-fetch-user: ?1',
        'upgrade-insecure-requests: 1',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if($request->proxy == 'off') {
      $this->proxy = null;
    }
    curl_setopt($ch, CURLOPT_PROXY, @$this->proxy->proxy_string);
    echo '<span style="    position: absolute;
    left: 0;
    top: 0;
    color: red;
    z-index: 9999999;">'.@$this->proxy->proxy_string.'</span>';
    $response = curl_exec($ch);
    die($response);
    curl_close($ch);
  }
}
