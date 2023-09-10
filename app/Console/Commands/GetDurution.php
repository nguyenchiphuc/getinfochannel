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
    $channels = Channel::orderBy('id','desc')->get();
    foreach ($channels as $channel1) {
      $videos = [];
      foreach ($channel1->videos as $item) {
        $duration = app('App\Http\Controllers\API\GetInfoController')->getDuration($item['href']);
        $item['duration'] = (int) $duration;
        $videos[] = $item;
      }
      $channel1->videos = $videos;
      $channel1->save();
    }
    return Command::SUCCESS;
  }
}
