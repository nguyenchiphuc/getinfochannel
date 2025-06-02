<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\GetVideoService;

class UpdateVideos extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'update:videos';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'UpdateVideos';
  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $channels = Channel::orderBy('updated_at', 'desc')->where('status', 1)->limit(50)->get();
    if($channels->count() == 0) {
        Channel::where('status', 0)->update(['status' => 1]);
    }
    foreach ($channels as $channel) {
        app('App\Services\GetVideoService')->getVideos($channel);
        $channel->status = 0;
        $channel->save();
        echo $channel->channel_id."\n";
    }
    return Command::SUCCESS;
  }
}
