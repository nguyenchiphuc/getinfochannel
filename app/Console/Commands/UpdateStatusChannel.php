<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class UpdateStatusChannel extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'update:status';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'UpdateStatusChannel';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    Channel::where('status', 1)->update(['status' => 0]);
    return Command::SUCCESS;
  }
}
