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
    Log::channel('custom')->alert('start update:videos '.date('Y-m-d H:i:s'));
    $channels = Channel::orderBy('updated_at', 'desc')->where('status', 1)->limit(50)->get();
    if($channels->count() == 0) {
        Channel::where('status', 0)->update(['status' => 1]);
    }
    foreach ($channels as $channel) {
      try {
        app('App\Services\GetVideoService')->getVideos($channel);
        $channel->status = 0;
        $channel->save();
        echo $channel->channel_id."\n";
      }catch (\Exception $e) {
        echo $e->getMessage();   
      }
    }
    Log::channel('custom')->alert('end update:videos '.date('Y-m-d H:i:s'));
    return Command::SUCCESS;
  }
}
