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
    $channels = Channel::orderBy('id', 'desc')->where('videos', null)->orWhere('current_subs', 0)->get();
    foreach ($channels as $channel) {
      app('App\Http\Controllers\API\GetInfoController')->getVideos($channel);
    }
    return Command::SUCCESS;
  }
}
