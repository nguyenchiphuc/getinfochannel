<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class GetDurution extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'get:durution';

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
      $videos = [];
      echo $channel->channel_id . "\n";
      $listVideo = app('App\Http\Controllers\API\GetInfoController')->getListVideoFunc($channel->channel_id, 0, 0);
      foreach ($channel->videos as $item) {
        if (count($listVideo) > 0) {
          foreach ($listVideo as $video1) {
            if ($video1['id'] == $item['id']) {
              @$item['duration'] = (int) @$video1['duration'];
            }
          }
        } else {
          $duration = app('App\Http\Controllers\API\GetInfoController')->getDuration($item['href']);
          @$item['duration'] = (int) $duration;
        }
        $videos[] = $item;
      }
      $channel->videos = $videos;
      $channel->save();
    }
    return Command::SUCCESS;
  }
}
