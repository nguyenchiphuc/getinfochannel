<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use App\Model\Setting;
use Response;
use Session;
use Route;
use Auth;
use Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Input;
use Carbon\Carbon;
use App\User;
use Cart;
use Log;
use App\Model\Action;
use App\Model\Reaction;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Goutte\Client;
class Helpers
{
  public static function sendTelegramError($message, $chatId = 1002121265286, $token = '6159396277:AAGEQ3KjI227tvYKu6K53xQpqC7IxPS0HIQ') {
    $data = [
        'text' => $message,
        'chat_id' => '-'.$chatId
    ];
    @file_get_contents("https://api.telegram.org/bot$token/sendMessage?" . http_build_query($data));
}
}