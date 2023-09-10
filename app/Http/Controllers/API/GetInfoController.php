<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\DB;
use App\Models\Channel;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

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
    if ($response != null) {
      $pattern = '/var ytInitialData = ({.*?});<\/script>/s';
      if (preg_match($pattern, $response, $matches)) {
        // $matches[1] chứa giá trị của ytInitialData
        $ytInitialData = $matches[1];
      }
      $items = json_decode($ytInitialData);
      $array = [];
      foreach ($items->contents->twoColumnBrowseResultsRenderer->tabs as $tabs) {
        if (@$tabs->tabRenderer->title == 'Video' && @$tabs->tabRenderer) {
          foreach ($tabs->tabRenderer->content->richGridRenderer->contents as $content) {
            if (@$content->richItemRenderer) {
              $video = $content->richItemRenderer->content->videoRenderer;
              $id = $video->videoId;
              $title = $video->title->runs[0]->text;
              $arr = [];
              $time = $video->lengthText->simpleText;
              $publishedTimeText = $video->publishedTimeText->simpleText;
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
              $array[] = $arr;
            }
          }
        }
      }
      $collection = collect($array)->filter(function ($item) use ($duration) {
        return $item['duration'] > $duration;
      })->values()->toArray();
      $collection = collect($collection)->filter(function ($item) use ($diffDay) {
        if ($diffDay == 1) {
          return strpos($item['publishedTimeText'], 'giờ') === false;
        } else if ($diffDay == 2) {
          return strpos($item['publishedTimeText'], 'giờ') === false && strpos($item['publishedTimeText'], '1 ngày') === false;
        } else if ($diffDay == 3) {
          return strpos($item['publishedTimeText'], 'giờ') === false && strpos($item['publishedTimeText'], '1 ngày') === false && strpos($item['publishedTimeText'], '2 ngày') === false;
        } else if ($diffDay == 4) {
          return strpos($item['publishedTimeText'], 'giờ') === false && strpos($item['publishedTimeText'], '1 ngày') === false && strpos($item['publishedTimeText'], '2 ngày') === false && strpos($item['publishedTimeText'], '3 ngày') === false;
        } else if ($diffDay == 5) {
          return strpos($item['publishedTimeText'], 'giờ') === false && strpos($item['publishedTimeText'], '1 ngày') === false && strpos($item['publishedTimeText'], '2 ngày') === false && strpos($item['publishedTimeText'], '3 ngày') === false && strpos($item['publishedTimeText'], '4 ngày') === false;
        }
        return $item;
      })->values()->toArray();
      return $collection;
    }
  }
  public function getListVideo(Request $request, $string)
  {
    $to = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now());

    $channel = Channel::where('channel_id', $string)->first();
    if (!$channel) {
      Channel::updateOrCreate(['channel_id' => $string], ['channel_id' => $string]);
    }
    if ($channel && $channel->videos) {
      $videosDiff = [];
      foreach ($channel->videos as $video) {
        $from = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::parse($video['published']));

        $video['diff'] = $to->diffInDays($from);
        $videosDiff[] = $video;
      }
      return $this->sendResponse($videosDiff, null);
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $string,
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

    curl_close($curl);
    $listOrderHis = [];
    // check xml
    if (preg_match('/<\?xml[^?]*\?>/', $response)) {
      $xmlObject = simplexml_load_string($response);
      $entry = $xmlObject->entry;
      $array = [];
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
          $arr['published'] = $published;
          $arr['duration'] = 0;


          $array[] = $arr;
        }
      }
      if (count($array) > 0) {
        $channel->videos = $array;
        $channel->save();
      }
      return $this->sendResponse($array, null);
    }
    return $this->sendResponse($listOrderHis, null);
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
