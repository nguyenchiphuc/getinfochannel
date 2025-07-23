<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Sheets;
use App\Models\Proxy;

class GetProxySheet extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'get:proxy-sheet';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'GetProxySheet';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $spreadsheetId = '1xsHdA3ENkTsZIRnmjNDB2roolTMr1NBt1p30ZZSigh0';
    $range = 'proxy_getinfochanel!A:A'; // Sheet name + range

    $client = new Client();
    $client->setAuthConfig(storage_path('app/google-service-account.json'));
    $client->addScope(Sheets::SPREADSHEETS_READONLY);
    $Proxy = Proxy::where('id', '>', 0);
    $Proxy->delete();
    $service = new Sheets($client);

    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues();
    

    $listProxy = array_filter(array_map(fn($row) => $row[0] ?? null, $values));
    foreach($listProxy as $proxy) {
      $proxyS = $proxy;
      $user = '';
      $pass = '';
      if (strpos($proxy, '@') !== false) {
        $proArr = explode('@', $proxy);
        $user1 = $proArr[0];
        $arrUser = explode(':', $user1);
        $user = $arrUser[0];
        $pass = $arrUser[1];
        $proxy = $proArr[1];
      }
        echo $proxyS."\n";
        $arrProxy = explode(':', $proxy);
        Proxy::updateOrCreate([
            'proxy_string' => $proxyS],[
            'proxy_string' => $proxyS,
            'ipv4' => @$arrProxy[0],
            'port' => @$arrProxy[1],
            'username' => $user,
            'password' => $pass,
            'live' => 1,
            'enable' => 1,
            'status' => 1
        ]);
    }
    return Command::SUCCESS;
  }
}
