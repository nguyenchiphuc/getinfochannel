<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
  protected $description = 'GetVideos';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $cutoff = Carbon::now()->subDays(1);
    $channels = Channel::where('created_at', '<', $cutoff)->delete();
    \App\Models\Log::where('created_at', '<', \Carbon\Carbon::now()->subDays(config('app.log_delete')))->delete();
    \App\Models\Channel::where('created_at', '<', \Carbon\Carbon::now()->subDays(config('app.channel_delete')))->delete();
    $limit = round(Channel::count() / config('app.limit') / 3) + 20;
    $channels = Channel::orderBy('id', 'desc')->where('status', 0)->limit($limit)->get();
    foreach ($channels as $channel) {
      echo $channel->channel_id . "\n";
      app('App\Http\Controllers\API\GetInfoController')->getVideos($channel);
    }
    return Command::SUCCESS;
  }
}
