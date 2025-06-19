<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Log;
use App\Models\Channel;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\Setting;
use App\Models\Proxy;
use App\Services\GetVideoService;
use App\Libraries\Helpers;

class GetInfoController extends BaseController
{
  protected $proxy = '';
  protected $getVideoService;
  protected $setting;
  public function __construct(GetVideoService $getVideoService) {
    $setting = Setting::where('id', 1)->first();
    $this->proxy = Proxy::getProxy();
    $this->setting = $setting;
    $this->getVideoService = $getVideoService;
  }
  
  public function getAPILink($channelId) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => config('app.api_link').$channelId,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $result = json_decode($response);
    if($result->resutls > 0) {
      $arr = [];
      foreach ($result->videos as $key => $value) {
        $arrNew = [];
        $arrNew['duration'] = $value->duration;
        $arrNew['id'] = $value->id;
        $arrNew['title'] = $value->title;
        $arrNew['publishedAt'] = $value->publishedAt;
        $arrNew['from'] = $value->from;
        $arrNew['callApi'] = true;
        $arr[] = $arrNew;
      }
      return (array) $arr;
    }
    return [];
  }

  public function getListVideo(Request $request, $string)
  {
    if($request->using_proxy) {
      $this->setting->using_proxy = $request->using_proxy;
    }
    $duration = $request->duration ? $request->duration : 60;
    $diffDay = $request->diffDay ? $request->diffDay : 0;

    $to = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now());
    $channel = Channel::where('channel_id', $string)->first();
    $params = ['connect_db' => 0, 'count_video' => @$channel->count_video, 'subscribe' => 0, 'videos' => [], 'channel_id' => $string, 'resutls' => 0];
    $params['using_proxy'] = @$this->setting->using_proxy;
    $params['proxy'] = @$this->proxy->proxy_string;
    if($channel && $channel->subscribe) {
      $params['connect_db'] = 1;
      $params['subscribe'] = @$channel->subscribe;
      $params['videos'] = @$channel->channel_videos;
      $params['resutls'] = @$channel->channel_videos ? count(@$channel->channel_videos) : 0;
      $params['short_videos'] = @$channel->short_videos;
      $params['short_resutls'] = @$channel->short_videos ? count(@$channel->short_videos) : 0;
      $channel->duration = $request->duration;
      $channel->diffDay = $request->diffDay;
      $channel->updated_at = Carbon::now();
      $channel->save();
      return $params;
    }
    
    if ($channel && ($channel->videos || $channel->channel_videos)) {
      $params['connect_db'] = 1;
    } else {
      $channel = Channel::updateOrCreate(
        ['channel_id' => $string],
        ['channel_id' => $string]
      );
      $lists = $this->getVideoService->getVideos($channel);
      $params['connect_db'] = 0;
    }
    $lists = $this->getVideoService->getVideos($channel);
    if(is_array($lists) && !empty($lists['error'])) {
      return response()->json($lists);
    }
    $params['order_link'] = $channel->order_link;
    $params['count_video'] = $channel->count_video;
    $videosDiff = [];
    if ($channel && ($channel->videos !=null || $channel->channel_videos !=null)) {
      $channelVideos = [];
      if($channel->channel_videos == '[]' || $channel->channel_videos == null) {
        $channel->channel_videos = [];
      }
      if($channel->videos == '[]' || $channel->videos == null) {
        $channel->videos = [];
      }
      if(count((array) $channel->channel_videos) > 0 && strpos(json_encode($channel->channel_videos), 'duration') !== false) {
        $channelVideos = $channel->channel_videos;
      }else if(count((array) $channel->videos) > 0 && strpos(json_encode($channel->videos), 'duration') !== false){
        $channelVideos = $channel->videos;
      }
      if(!is_array($channelVideos)) {
        $channelVideos = json_decode($channelVideos);
      }
      if($channel->order_link == 'channel_is_die') {
        $channelVideos = [];
      }
      foreach ($channelVideos as $video) {
        $video = (array) $video;
        $from = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::parse($video['publishedAt']));
        $video['diff'] = $to->diffInDays($from);
        $videosDiff[] = $video;
      }
      $params['subscribe'] = $channel->current_subs;
      $channel->subscribe = $channel->current_subs;
      $channel->save();
      $collection = collect($videosDiff)->filter(function ($item) use ($duration) {
        return @$item['duration'] >= $duration;
      })->values()->toArray();
      $collection = collect($collection)->filter(function ($item) use ($diffDay) {
        return @$item['diff'] > $diffDay - 1;
      })->values()->toArray();
      $params['videos'] = $collection;
      $params['short_videos'] = $channel->short_videos;
      $params['resutls'] = count($collection);
    }

    return response()->json($params);
  }
}
