<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Setting;
use App\Models\Proxy;
use Illuminate\Support\Facades\Log;

class GetProxy extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'get:proxy';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'GetProxy';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $setting = Setting::first();
    $api_proxy_get = $setting->api_proxy_get ? $setting->api_proxy_get : 'https://proxy-get-channel.nguyenchiphuc.com/api/v2/proxy/get?geo=vn';
    $proxies = Proxy::where('status', 1)->orderBy('count_used', 'asc')->where('live', 1)->limit(10)->get();
    echo "update live proxy\n";
    foreach($proxies as $proxy) {
        echo "check live ".$proxy->proxy_string . "\n";
        $live = $this->checkLive($proxy->proxy_string);
        if(!$live) {
            echo "update not live ".$proxy->proxy_string . "\n";
            $proxy->update(['live' => 0, 'status' => 0]);
        }
    }
    for($i= 0; $i<10;$i++) {
        $contents = file_get_contents($api_proxy_get);
        $proxy = json_decode($contents, true);
        if($proxy && $proxy['success'] == 'true' ) {
            $newProxy = $proxy['data'];
            // 103.82.38.9:22185:OVfN6:VMGZ9
            $arrProxy = explode(':', $newProxy);
            $user = '';
            $pass = '';
            $ipv4 = '';
            $port = '';
            $proxy_string = '';
            if(count($arrProxy) == 4) {
                $ipv4 = $arrProxy[0];
                $port = $arrProxy[1];
                $user = $arrProxy[2];
                $pass = $arrProxy[3];
                $proxy_string = $user.':'.$pass.'@'.$ipv4.':'.$port;
            }else if(count($arrProxy) == 2) {
                $ipv4 = $arrProxy[0];
                $port = $arrProxy[1];
                $proxy_string = $newProxy;
            }
            $live = $this->checkLive($proxy_string);
            if($live) {
                echo "insert ".$proxy_string . "\n";
                Proxy::updateOrCreate(['proxy_string' => $proxy_string],[
                    'ipv4' => $ipv4,
                    'port' => $port,
                    'username' => $user,
                    'password' => $pass,
                    'status' => 1,
                    'enable' => 1,
                    'live' => 1,
                    'count_used' => 0,
                    'proxy_string' => $proxy_string,
                    'proxy_get' => $newProxy,
                ]);
            }else{
                echo "not insert ".$proxy_string . "\n";
            }
        }
    }
    Proxy::where('live', 0)->delete();
    // check live proxy
    
    return Command::SUCCESS;
  }

  public function checkLive($proxy) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.youtube.com',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_PROXY => $proxy,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Cookie: GPS=1; VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D; YSC=ZaE57vZNiI8'
        ),
      ));
    $response = curl_exec($curl);
    if (!$response && curl_errno($curl)) {
        return false;
    }
    curl_close($curl);
    if(strpos($response, 'youtube') !== false) {
        return true;
    }
    return false;
  }
}
