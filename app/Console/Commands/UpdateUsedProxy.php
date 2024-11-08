<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Setting;
use App\Models\Proxy;
use Illuminate\Support\Facades\Log;

class UpdateUsedProxy extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'update:proxy';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'UpdateUsedProxy';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $setting = Setting::first();
    Proxy::whereIn('status', [1, 0])->update(['count_used' => 0]);
    return Command::SUCCESS;
  }

}
