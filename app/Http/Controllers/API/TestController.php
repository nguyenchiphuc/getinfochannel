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
use App\Libraries\Helpers;

class TestController extends BaseController
{
  protected $proxy = '';
  protected $setting;
  public function __construct() {
    $setting = Setting::where('id', 1)->first();
    $this->proxy = Proxy::getProxy();
    $this->setting = $setting;
  }

  public function testUrl(Request $request) {
    $url = $request->url;
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    preg_match('/room_id\=([^\"]+)?/', $response, $output_array);
    if(count($output_array) < 2) {
      preg_match('/roomId\"\:\"([^\"]+)?/', $response, $output_array);
    }
    preg_match('/uniqueId\"\:\"([^\"]+)?/', $response, $username1);
    if(count($output_array) == 2) {
      $room_id = @$output_array[1];
     if(count($output_array) == 2 && @$output_array[1] > 0) {
       if(count($username1) ==2) {
         $uniqueId = $username1[1];
         if (strpos($url, $uniqueId) !== false) {
           $params = ['success' => 1, 'room_id' => @$output_array[1]];
           return response()->json($params);
         }
       }
     }
    }
    return ['success' => 0];

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

  public function getListVideoFuncFeatured($channel, $duration = 60, $diffDay = 0)
  {
    $channelId = $channel->channel_id;
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://www.youtube.com/channel/' . $channelId . '/featured',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_PROXY => $this->proxy->proxy_string,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Cookie: GPS=1; VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D; YSC=ZaE57vZNiI8'
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $collection = collect([]);
    if ($response != null) {
      $pattern = '/var ytInitialData = ({.*?});<\/script>/s';
      if (preg_match($pattern, $response, $matches)) {
        // $matches[1] chứa giá trị của ytInitialData
        $ytInitialData = $matches[1];
        $items = json_decode($ytInitialData);
        $array = [];
        if (!empty($items->contents)) {
          foreach ($items->contents->twoColumnBrowseResultsRenderer->tabs as $tabs) {
          //   echo "<pre>";
          //     print_r($items);
          // echo "</pre>";

            if (@$tabs->tabRenderer->title == 'Trang chủ') {
              foreach ($tabs->tabRenderer->content->sectionListRenderer->contents as $content) {
                if(empty($content->itemSectionRenderer)){
                  Log::error('not find itemSectionRenderer ' . $channelId);
                  continue;
                }
                foreach ($content->itemSectionRenderer->contents as $key => $value) {
                  if(empty($value->shelfRenderer)){
                    Log::error('not find shelfRenderer ' . $channelId);
                    continue;
                  }
                  if(empty($value->shelfRenderer->content)){
                    Log::error('not find shelfRenderer->content ' . $channelId);
                    continue;
                  }
                  if(empty($value->shelfRenderer->content->horizontalListRenderer)){
                    Log::error('not find shelfRenderer->content->horizontalListRenderer ' . $channelId);
                    continue;
                  }
                  foreach ($value->shelfRenderer->content->horizontalListRenderer->items as $item1) {
                    $video = @($item1->gridVideoRenderer);
                    $publishedTimeText = null;
                    $id = @$video->videoId;
                    $title = @$video->title->simpleText;
                    $arr = [];
                    $time = !empty($video->thumbnailOverlays[0]->thumbnailOverlayTimeStatusRenderer->text->simpleText) ? $video->thumbnailOverlays[0]->thumbnailOverlayTimeStatusRenderer->text->simpleText : '';
                    if (!$time) {
                      Log::error('not find time ' . $channelId);
                      continue;
                    }

                    $publishedTimeText = !empty($video->publishedTimeText) ? @$video->publishedTimeText->simpleText : '';
                    if (!$publishedTimeText) {
                      Log::error('not find publishedTimeText ' . $channelId);
                      continue;
                    }
                    $timeKeywords = [
                      'năm' => 'subYears',
                      'tháng' => 'subMonths',
                      'tuần' => 'subWeeks',
                      'ngày' => 'subDays',
                      'giờ' => 'subHours',
                      'phút' => 'subMinutes',
                      'years' => 'subYears',
                      'months' => 'subMonths',
                      'weeks' => 'subWeeks',
                      'days' => 'subDays',
                      'hours' => 'subHours',
                      'minutes' => 'subMinutes',
                    ];
                    $newDateTime = Carbon::now();
                    // Tìm từ khóa thời gian trong chuỗi $publishedTimeText
                    foreach ($timeKeywords as $keyword => $method) {
                      if (strpos($publishedTimeText, $keyword) !== false) {
                        $arrTime = explode(" $keyword ", $publishedTimeText);
                        if (count($arrTime)) {
                          $timeCurrent = (int) str_replace('Phát trực tiếp ', '', $arrTime[0]) + 1;
                          $newDateTime = Carbon::now()->$method($timeCurrent);
                          break; // Thoát khỏi vòng lặp khi đã tìm thấy từ khóa thời gian
                        }
                      }
                    }
                    $timeArr = explode(":", $time);
                    if (count($timeArr) > 2) {
                      $durationTime = $timeArr[0] * 60 * 60 + $timeArr[1] * 60 + $timeArr[2];
                    } else if (count($timeArr) > 1) {
                      $durationTime = $timeArr[0] * 60 + $timeArr[1];
                    } else {
                      $durationTime = $timeArr[0];
                    }
                    $arr['duration'] = $durationTime;
                    $arr['time'] = $time;
                    $arr['id'] = $id;
                    $arr['featured'] = true;
                    $arr['title'] = $title;
                    $arr['publishedTimeText'] = $publishedTimeText;
                    $arr['publishedAt'] = $newDateTime->format('Y-m-d H:i:s');
                    $array[] = $arr;
                  }
                }
              }
            }
          }
          $collection = collect($array)->filter(function ($item) use ($duration) {
            return $item['duration'] > $duration;
          })->values()->toArray();
          return $collection;
        }
      }
    }
    return $collection;
  }

  public function randomProxy($proxy) {
    $proxyArr = explode('|', $proxy);
    $index = array_rand($proxyArr,1);
    return $proxyArr[$index];
  }

  public function getListVideoFunc($channel, $duration = 60, $diffDay = 0, $typeChannel = 'videos')
  {
    $channelId = $channel->channel_id;
    $curl = curl_init();
    if($this->setting->using_proxy) {
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.youtube.com/channel/' . $channelId . '/'.$typeChannel,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_PROXY => $this->proxy->proxy_string,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Cookie: GPS=1; VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D; YSC=ZaE57vZNiI8'
        ),
      ));
      $channel->proxy = $this->proxy->proxy_string;
      $this->proxy->count_used = $this->proxy->count_used + 1;
      $this->proxy->save();
      $channel->save();
    }else{
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.youtube.com/channel/' . $channelId . '/'.$typeChannel,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Cookie: GPS=1; VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D; YSC=ZaE57vZNiI8'
        ),
      ));
      $channel->proxy = null;
      $channel->save();
    }
    
    $response = curl_exec($curl);
    if (!$response && curl_errno($curl)) {
      $collection = ['error' => ($this->setting->using_proxy ? $this->proxy->proxy_string : 'No proxy')." error time out ".curl_error($curl)];
      return $collection;
    }
    curl_close($curl);
    
    $collection = collect([]);
    if ($response != null) {
      
      if (strpos($response, 'Tài khoản này đã bị chấm dứt do chúng tôi nhận được nhiều đơn khiếu nại của bên thứ ba về việc vi phạm bản quyền liên quan đến tư liệu mà người dùng này đăng tải.') !== false || strpos($response, 'Kênh này đã bị gỡ bỏ do vi phạm Nguyên tắc cộng đồng của chúng tôi.') !== false) {
        $channel->order_link = 'channel_is_die';
        $channel->save();
      }
      preg_match('/\}\,\{\"text\"\:\{\"content\"\:\"([^\"]+)?/', $response, $output_array);
      if(count($output_array) == 2) {
        $count = $output_array[1];
        if(strpos($count, 'N video') !== false) {
          $count = str_replace('N video', '', $count);
          $count = trim($count);
          $count = str_replace("\u{A0}", "", $count);
          if(strpos($count, ',') !== false) {
            $count = str_replace(",", "", $count);
            $count = $count.'00';
          }else{
            $count = $count.'000';
          }
        }
        if(strpos($count, ' video') !== false) {
          $count = str_replace(' video', '', $count);
        }
        $channel->count_video =  (int) $count;
        $channel->order_link = null;
        $channel->save();
      }
      $pattern = '/var ytInitialData = ({.*?});<\/script>/s';
      if (preg_match($pattern, $response, $matches)) {
        // $matches[1] chứa giá trị của ytInitialData
        $ytInitialData = $matches[1];
        $items = json_decode($ytInitialData);
        $array = [];
        $titleGet = $typeChannel == 'videos' ? 'Video' : 'Sự kiện phát trực tiếp';
        if (strpos($response, '"title":"Video phát trực tiếp"') !== false && $typeChannel != 'videos') {
          $titleGet = 'Video phát trực tiếp';
          $logs = ['titleGet' => $titleGet];
          $channel->logs = ($logs);
        }
        if($typeChannel == 'videos') {
          $titleGet = 'Video';
        }
        if($typeChannel == 'streams') {
          $titleGet = 'Video phát trực tiếp';
        }
        if($typeChannel == 'shorts') {
          $titleGet = 'Shorts';
        }
        $shortsArr = [];
        try{
          if(!empty($items->contents)){
            foreach ($items->contents->twoColumnBrowseResultsRenderer->tabs as $tabs) {
              if ((strpos(@$tabs->tabRenderer->title, $titleGet) !== false) && !empty($tabs->tabRenderer)) {

                if(empty($tabs->tabRenderer->content->richGridRenderer)){
                  Log::error('not find tabRenderer->content->richGridRenderer ' . $channelId);
                  continue;
                }
                foreach ($tabs->tabRenderer->content->richGridRenderer->contents as $content) {

                  if (@$content->richItemRenderer->content->shortsLockupViewModel) {
                    $shortsLockupViewModel = @$content->richItemRenderer->content->shortsLockupViewModel;
                    // echo json_encode($shortsLockupViewModel)."\n";
                    // echo "<br>";
                    $accessibilityText = $shortsLockupViewModel->accessibilityText;
                    $entityId = $shortsLockupViewModel->entityId;
                    $arrTitle = explode(',', $accessibilityText);
                    $title = $arrTitle[0];
                    $entityIdArr = explode('shorts-shelf-item-', $entityId);
                    $idShort = 'short/'.$entityIdArr[1];
                    $shortArr = [];
                    $shortArr['id'] = $idShort;
                    $shortArr['title'] = $title;
                    $shortsArr[] = $shortArr;
                  }
                }
              }
            }
            foreach ($items->contents->twoColumnBrowseResultsRenderer->tabs as $tabs) {
              if ((strpos(@$tabs->tabRenderer->title, $titleGet) !== false) && !empty($tabs->tabRenderer)) {

                if(empty($tabs->tabRenderer->content->richGridRenderer)){
                  // echo 'not find tabRenderer->content->richGridRenderer ' . $channelId;
                  Log::error('not find tabRenderer->content->richGridRenderer ' . $channelId);
                  continue;
                }
                foreach ($tabs->tabRenderer->content->richGridRenderer->contents as $content) {
                  if (@$content->richItemRenderer) {
                    $publishedTimeText = null;
                    $video = $content->richItemRenderer->content->videoRenderer;
                    $id = $video->videoId;
                    $title = $video->title->runs[0]->text;
                    $arr = [];
                    $time = !empty($video->lengthText) ? $video->lengthText->simpleText : '';
                    if(!$time) {
                      Log::error('not find time ' . $channelId);
                      continue;
                    } 
                    $publishedTimeText = !empty($video->publishedTimeText) ? @$video->publishedTimeText->simpleText : '';
                    if(!$publishedTimeText) {
                      Log::error('not find publishedTimeText ' . $channelId);
                      continue;
                    } 
                    $timeKeywords = [
                      'năm' => 'subYears',
                      'tháng' => 'subMonths',
                      'tuần' => 'subWeeks',
                      'ngày' => 'subDays',
                      'giờ' => 'subHours',
                      'phút' => 'subMinutes',
                      'years' => 'subYears',
                      'months' => 'subMonths',
                      'weeks' => 'subWeeks',
                      'days' => 'subDays',
                      'hours' => 'subHours',
                      'minutes' => 'subMinutes',
                    ];
                    $newDateTime = Carbon::now();
                    // Tìm từ khóa thời gian trong chuỗi $publishedTimeText
                    foreach ($timeKeywords as $keyword => $method) {
                      if (strpos($publishedTimeText, $keyword) !== false) {
                        $arrTime = explode(" $keyword ", $publishedTimeText);
                        if (count($arrTime)) {
                          $timeCurrent = (int) str_replace('Phát trực tiếp ', '', $arrTime[0]) + 1;
                          $newDateTime = Carbon::now()->$method($timeCurrent);
                          break; // Thoát khỏi vòng lặp khi đã tìm thấy từ khóa thời gian
                        }
                      }
                    }
  
                    $timeArr = explode(":", $time);
                    if (count($timeArr) > 2) {
                      $durationTime = $timeArr[0] * 60 * 60 + $timeArr[1] * 60 + $timeArr[2];
                    } else if (count($timeArr) > 1) {
                      $durationTime = $timeArr[0] * 60 + $timeArr[1];
                    } else {
                      $durationTime = $timeArr[0];
                    }
                    $arr['duration'] = $durationTime;
                    $arr['time'] = $time;
                    $arr['id'] = $id;
                    $arr['title'] = $title;
                    $arr['publishedTimeText'] = $publishedTimeText;
                    $arr['publishedAt'] = $newDateTime->format('Y-m-d H:i:s');
                    $array[] = $arr;
                  }
                }
              }
            }
          }
        }catch (\Exception $e) {
          Log::error($channelId.' $items->contents->twoColumnBrowseResultsRenderer->tabs '.$e->getMessage());   
        }
        if($typeChannel == 'shorts') {
          $channel->short_videos = $shortsArr;
          $channel->save();
        }
        $collection = collect($array)->filter(function ($item) use ($duration) {
          return $item['duration'] > $duration;
        })->values()->toArray();
        return $collection;
      } else {
        Helpers::sendTelegramError('not find ytInitialData ' . $channelId.'-'.\Request::getHost());
        Log::error('not find ytInitialData ' . $channelId);
      }
    }
    return $collection;
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
    $params['using_proxy'] = $this->setting->using_proxy;
    $params['proxy'] = $this->proxy->proxy_string;
    if ($channel && ($channel->videos || $channel->channel_videos)) {
      $params['connect_db'] = 1;
    } else {
      $channel = Channel::updateOrCreate(
        ['channel_id' => $string],
        ['channel_id' => $string]
      );
      $lists = $this->getVideos($channel);
      $params['connect_db'] = 0;
    }
    $lists = $this->getVideos($channel);
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

  public function getVideos($channel)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channel->channel_id,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Cookie: VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D'
      ),
    ));
    $callApi = false;
    $response = curl_exec($curl);
    $this->getListVideoFunc($channel, 60, 0, 'shorts');
    $listVideo = $this->getListVideoFunc($channel);
    if(is_array($listVideo) && !empty($listVideo['error'])) {
      return ['error' => $listVideo['error']];
    }
    // if(count($listVideo) == 0) {
    //   $listVideo = $this->getListVideoFuncFeatured($channel->channel_id, 60, 0);
    // }
    if(count($listVideo) == 0) {
      $listVideo = $this->getListVideoFunc($channel, 60, 0, 'streams');
    }
    if(count($listVideo) == 0){
      // $listVideo = $this->getAPILink($channel->channel_id);
    } 
    curl_close($curl);
    // check xml
    if (preg_match('/<\?xml[^?]*\?>/', $response)) {
      $array = [];
      try{
        $xmlObject = simplexml_load_string($response);
        $entry = $xmlObject->entry;
        if (count($entry) > 0) {
          foreach ($entry as $item) {
            $arr = [];
            $video_id = str_replace('yt:video:', '', $item->id->__toString());
            $href = 'https://www.youtube.com/watch?v=' . $video_id;
            $arr['id'] = $video_id;
            // $arr['du'] = $this->getDuration($href);
            $arr['href'] = $href;
            $title = $item->title->__toString();
            $arr['title'] = $title;
            $published = $item->published->__toString();
            $arr['publishedAt'] = $published;
            if (count($listVideo) > 0) {
              foreach ($listVideo as $video1) {
                if ($video1['id'] == $video_id) {
                  $arr['duration'] = (int) $video1['duration'];
                }
              }
            }

            $array[] = $arr;
          }
        }
      }catch (\Exception $e) {
        Log::error($channel->channel_id.' simplexml_load_string($response) '.$e->getMessage());   
      }
      if (count($array) > 0) {
        $channel->videos = $array;
        if(count($listVideo) > 0) {
          $channel->channel_videos = $listVideo;
        }
        $channel->save();
      }else{
        $channel->videos = [];
        if(count($listVideo) > 0) {
          $channel->channel_videos = $listVideo;
        }
        $channel->save();
      }
    }else {
      $channel->videos = [];
      if(count($listVideo) > 0) {
        $channel->channel_videos = $listVideo;
      }
      $channel->save();
    }
  }

  public function getDuration($link)
  {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $link,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Cookie: GPS=1; VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D; YSC=k74t4OreAKY'
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $pattern = '/\"lengthSeconds\"\:\"([^\"]+)?\"/s';
    if (preg_match($pattern, $response, $matches)) {
      return $matches[1];
    }
    return 0;
  }
}
