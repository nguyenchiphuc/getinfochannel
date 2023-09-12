<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Log;
use App\Models\Channel;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use GuzzleHttp\Client;

class GetInfoController extends BaseController
{
  public function getListVideoFunc($channelId, $duration = 60, $diffDay = 0)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://www.youtube.com/channel/' . $channelId . '/videos',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
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
        try{
          if(!empty($items->contents)){
            foreach ($items->contents->twoColumnBrowseResultsRenderer->tabs as $tabs) {
              if ((strpos(@$tabs->tabRenderer->title, 'Video') !== false) && !empty($tabs->tabRenderer)) {
                foreach ($tabs->tabRenderer->content->richGridRenderer->contents as $content) {
                  if (@$content->richItemRenderer) {
                    $publishedTimeText = null;
                    $video = $content->richItemRenderer->content->videoRenderer;
                    $id = $video->videoId;
                    $title = $video->title->runs[0]->text;
                    $arr = [];
                    $time = $video->lengthText->simpleText;
                    $publishedTimeText = @$video->publishedTimeText->simpleText;
                    $timeKeywords = [
                      'năm' => 'subYears',
                      'tháng' => 'subMonths',
                      'ngày' => 'subDays',
                      'giờ' => 'subHours',
                      'phút' => 'subMinutes',
                    ];
                    $newDateTime = Carbon::now();
                    // Tìm từ khóa thời gian trong chuỗi $publishedTimeText
                    foreach ($timeKeywords as $keyword => $method) {
                      if (strpos($publishedTimeText, $keyword) !== false) {
                        $arrTime = explode(" $keyword ", $publishedTimeText);
                        if (count($arrTime)) {
                          $timeCurrent = (int) $arrTime[0];
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
        $collection = collect($array)->filter(function ($item) use ($duration) {
          return $item['duration'] > $duration;
        })->values()->toArray();
        return $collection;
      } else {
        Log::error('not find ytInitialData ' . $channelId);
      }
    }
    return $collection;
  }
  public function getListVideo(Request $request, $string)
  {
    $duration = $request->duration ? $request->duration : 60;
    $diffDay = $request->diffDay ? $request->diffDay : 0;

    $to = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now());
    $channel = Channel::where('channel_id', $string)->first();
    $params = ['connect_db' => 0, 'subscribe' => 0, 'videos' => [], 'channel_id' => $string, 'resutls' => 0];
    if ($channel && ($channel->videos || $channel->channel_videos)) {
      $params['connect_db'] = 1;
    } else {
      $channel = Channel::updateOrCreate(
        ['channel_id' => $string],
        ['channel_id' => $string]
      );
      $this->getVideos($channel);
      $params['connect_db'] = 0;
    }
    $this->getVideos($channel);

    $videosDiff = [];
    if ($channel && ($channel->videos || $channel->channel_videos)) {
      if(strpos(json_encode($channel->channel_videos), 'duration') !== false) {
        $channelVideos = $channel->channel_videos;
      }else if(strpos(json_encode($channel->videos), 'duration') !== false){
        $channelVideos = $channel->videos;
      }
      foreach ($channelVideos as $video) {
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

    $response = curl_exec($curl);
    $listVideo = $this->getListVideoFunc($channel->channel_id);
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
        $channel->channel_videos = $listVideo;
        $channel->save();
      }
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
