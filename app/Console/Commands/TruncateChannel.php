<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TruncateChannel extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'delete:channel';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'TruncateChannel';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    Channel::truncate();
    return Command::SUCCESS;
  }
}
