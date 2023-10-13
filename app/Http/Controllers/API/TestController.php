<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Log;
use App\Models\Channel;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use GuzzleHttp\Client;

class TestController extends BaseController
{
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

  public function getListVideoFuncFeatured($channelId, $duration = 60, $diffDay = 0)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://www.youtube.com/channel/' . $channelId . '/featured',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_PROXY => config('app.proxy'),
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
                foreach ($content->itemSectionRenderer->contents as $key => $value) {
                  if(empty($value->shelfRenderer)){
                    continue;
                  }
                  foreach ($value->shelfRenderer->content->horizontalListRenderer->items as $item1) {
                    $video = ($item1->gridVideoRenderer);
                    $publishedTimeText = null;
                    $id = $video->videoId;
                    $title = $video->title->simpleText;
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

  public function getListVideoFunc($channelId, $duration = 60, $diffDay = 0, $typeChannel = 'videos')
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://www.youtube.com/channel/' . $channelId . '/'.$typeChannel,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_PROXY => config('app.proxy'),
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
        $titleGet = $typeChannel == 'videos' ? 'Video' : 'Sự kiện phát trực tiếp';
        // try{
          if(!empty($items->contents)){
            foreach ($items->contents->twoColumnBrowseResultsRenderer->tabs as $tabs) {
              if ((strpos(@$tabs->tabRenderer->title, $titleGet) !== false) && !empty($tabs->tabRenderer)) {
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
        // }catch (\Exception $e) {
        //   Log::error($channelId.' $items->contents->twoColumnBrowseResultsRenderer->tabs '.$e->getMessage());   
        // }
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
      $channelVideos = [];
      if($channel->channel_videos > 0 && strpos(json_encode($channel->channel_videos), 'duration') !== false) {
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
      CURLOPT_PROXY => config('app.proxy'),
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Cookie: VISITOR_INFO1_LIVE=8Q0vdu_YCKE; VISITOR_PRIVACY_METADATA=CgJWThICGgA%3D'
      ),
    ));
    $callApi = false;
    $response = curl_exec($curl);
    $listVideo = $this->getListVideoFunc($channel->channel_id);
    if(count($listVideo) == 0) {
      $listVideo = $this->getListVideoFuncFeatured($channel->channel_id, 60, 0);
    }
    if(count($listVideo) == 0) {
      $listVideo = $this->getListVideoFunc($channel->channel_id, 60, 0, 'streams');
    }
    if(count($listVideo) == 0){
      $listVideo = $this->getAPILink($channel->channel_id);
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
        $channel->channel_videos = $listVideo;
        $channel->save();
      }
    }else{
      $channel->videos = [];
      $channel->channel_videos = $listVideo;
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
