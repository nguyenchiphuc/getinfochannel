<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class GetVideos extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'get:videos';

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
    $channels = Channel::orderBy('id', 'desc')->get();

    foreach ($channels as $channel) {
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channel->channel_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Cookie: VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D'
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      // check xml
      if (preg_match('/<\?xml[^?]*\?>/', $response)) {
        $xmlObject = simplexml_load_string($response);
        $entry = $xmlObject->entry;
        $array = [];
        if (count($entry) > 0) {
          foreach ($entry as $item) {
            $arr = [];
            $video_id = str_replace('yt:video:', '', $item->id->__toString());
            $href = 'https://www.youtube.com/watch?v=' . $video_id;
            $arr['id'] = $video_id;
            // $arr['du'] = $this->getDuration($href);
            $arr['href'] = $href;
            $title = $item->title->__toString();
            $arr['title'] = $title;
            $published = $item->published->__toString();
            $arr['published'] = $published;
            $arr['duration'] = 0;

            if(strpos($title, '#shorts') === false){
              $array[] = $arr;
            }
          }
        }
        if (count($array) > 0) {
          $channel->videos = $array;
          $channel->save();
        }
      }
    }
    return Command::SUCCESS;
  }
}
