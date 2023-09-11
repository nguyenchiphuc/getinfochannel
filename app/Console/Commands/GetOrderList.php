<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GetOrderList extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'get:order-list';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'GetDurution';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $states = ['pending'];
    $state = 'pending';
    $items =  User::where('status', 1)->get();
    foreach ($items as $item) {
      foreach ($states as $key => $state) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $item->api_url . '/order/list?state=' . $state,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $item->token
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $res = json_decode($response);

        if ($res && $res->data) {
          foreach ($res->data as $data) {
            $channel_id = $data->channel_id;
            if(!empty($channel_id)){
              $channel = Channel::updateOrCreate(
                [
                  'channel_id' => $channel_id,
                  'order_link' => $data->order_link,
                  'current_subs' => !empty($data->current_sub) ? $data->current_sub : 0
                ],
                [
                  'channel_id' => $channel_id,
                  'order_link' => $data->order_link,
                  'api_url' => $item->api_url,
                  'current_subs' => !empty($data->current_sub) ? $data->current_sub : 0
                ]
              );
              app('App\Http\Controllers\API\GetInfoController')->getVideos($channel);
              echo $channel_id."\n";
            }
          }
        }
      }
    }
    return Command::SUCCESS;
  }
}
