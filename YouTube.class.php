<?php

/**
* Class for interacting with YouTube API v3
* @since: 23rd Feburary, 2017
* @author: Saqib Razzaq (https://github.com/saqirzzq)
*/

class Youtube {

  private $apiKey;
  private $videosLimit = 20;
  private $channelsLimit = 20;
  private $apiBaseUrl = 'https://www.googleapis.com/youtube/v3/search';
  private $videosBaseUrl = 'https://www.googleapis.com/youtube/v3/videos';
  private $channelsBaseUrl = 'https://www.googleapis.com/youtube/v3/channels';

  function __construct($youtubeApiKey) {
    $this->apiKey = $youtubeApiKey;
    $this->videoSearchBaseUrl = $this->apiBaseUrl . '?type=videos';
    $this->channelsSearchBaseUrl = $this->apiBaseUrl . '?type=channels';  
  }

  /**
  * Searches YouTube videos against given query
  * @param : { string } { $query } { search term to be used }
  * @param : { integer } { $videosLimit } { max videos to fetch }
  * @param : { string } { $token } { token to fetch next page vids }
  * @param : { string } { $sort } { sorting of vidds }
  * @return : { array } { an array with required details }
  */

  public function search($query, $videosLimit = false, $token = false, $sort = false) {
    try {
      $buildQuery = $this->videoSearchBaseUrl . '&part=snippet&q=' . $query . '&key=' . $this->apiKey;
      if ($token) {
        $buildQuery .= '&pageToken=' . $token;
      }

      if ($videosLimit) {
        $this->videosLimit = $videosLimit;
      }

      $buildQuery .= '&maxResults=' . $this->videosLimit;
      $buildQuery = $this->cleanupQuery($buildQuery);

      if ($sort) {
        switch ($sort) {
          case 'views':
            $buildQuery .= '&order=viewCount';
            break;
          case 'likes':
            $buildQuery .= '&order=rating';
            break;
          case 'published_desc':
            $buildQuery .= '&order=date';
            break;
          default:
            $buildQuery .= '&order=relevance';
            break;
        }
      }

      $readable = $this->getContents($buildQuery, true);
      if (!$readable) {
        throw new Exception('Something went wrong trying to fetch videos data');
      }
      return $this->processSearch($readable, $token);
    } catch (Exception $e) {
      echo $e;
    }
  }

  public function searchChannels($query, $videosLimit = false, $token = false, $sort = false) {
    try {
      $buildQuery = $this->channelsSearchBaseUrl . '&part=snippet&q=' . $query . '&key=' . $this->apiKey;
      if ($token) {
        $buildQuery .= '&pageToken=' . $token;
      }

      if ($videosLimit) {
        $this->videosLimit = $videosLimit;
      }

      $buildQuery .= '&maxResults=' . $this->videosLimit;
      $buildQuery = $this->cleanupQuery($buildQuery);

      $readable = $this->getContents($buildQuery, true);
      if (!$readable) {
        throw new Exception('Something went wrong trying to fetch channels data');
      }
      return $this->processSearch($readable, $token);
    } catch (Exception $e) {
      echo $e;
    }
  }

  /**
  * Fetch related vids using id of video
  * @param : { string } { $id } { id of youtube video }
  * @param : { integer } { $max_vids } { number of max vids }
  * @param : { string } { $token } { token to get more vids }
  * @return : { array } { cleaned array with only needed details }
  */

  public function getRelatedVideos($videoId, $videosLimit = 8, $token = false) {
    try {
      if (!empty($videoId)) {
        $buildQuery = $this->apiBaseUrl . '&type=video&relatedToVideoId=' . $videoId;
        $buildQuery .= '&maxResults=' . $videosLimit . '&ke=' . $this->apiKey;
        if ($token) {
          $buildQuery .= '&pageToken=' . $token;
        }
        $readable = $this->getContents($buildQuery, true);
        if (!$readable) {
          throw new Exception('Something went wrong trying to fetch related videos data');
        }
        return $this->processSearch($readable, false, true);
      } else {
        throw new Exception('Invalid video id provided');
      }
    } catch (Exception $e) {
      echo $e;
    }
  }

  /**
  * Cleans raw data and returns req fields only
  * @param : { array } { $data } { array of raw api data }
  * @param : { boolean } { $more } { pass true when cleaning next page vids }
  * @param : { boolean } { $related } { pass true when cleaning related vids }
  * @return : { array } { array of cleaned data }
  */

  private function processSearch($data, $more = false, $related = false) {
    try {
      if (is_object($data)) {
        $cleanData = array();
        $cleanData['nextToken'] = $data->nextPageToken;
        $cleanData['total'] = $data->pageInfo->totalResults;
        $searchResults = $data->items;
        foreach ($searchResults as $key => $currentVideo) {
          $snippet = $currentVideo->snippet;
          $created = $snippet->publishedAt;
          if (!isset($currentVideo->id->videoId)) {
            continue;
          }
          $currentVideoId = $currentVideo->id->videoId;
          $cleanData['videos'][$key]['videoId'] = $currentVideoId;
          $published = substr($created, 0, strpos($created, "T"));
          $contentDetails = $this->getContentDetails($currentVideoId);

          $cleanData['videos'][$key]['title'] = $snippet->title;
          $cleanData['videos'][$key]['published'] = $published;
          $cleanData['videos'][$key]['description'] = $snippet->description;

          $cleanData['videos'][$key]['views'] = number_format($contentDetails->items[0]->statistics->viewCount);
          $cleanData['videos'][$key]['duration'] = $this->convertYouTubeTime($contentDetails->items[0]->contentDetails->duration);
          $cleanData['videos'][$key]['thumbnails'] = (array) $snippet->thumbnails;

          $cleanData['videos'][$key]['channelId'] = $snippet->channelId;
          $cleanData['videos'][$key]['channelTitle'] = $snippet->channelTitle;
        }

        pex($cleanData);
        return $cleanData;
      } else {
        throw new Exception('Invalid data provided');
      }
    } catch (Exception $e) {
      echo $e;
    }
  }

  private function getContentDetails($videoId, $type = false) {
    try {
      if (!empty($videoId)) {
        $buildQuery = $this->videosBaseUrl . '?id=' . $videoId . '&key=' . $this->apiKey;
        $buildQuery .= '&part=contentDetails,statistics';
        if ($type = 'all') {
          $buildQuery .= ',snippet';
        }

        $readable = $this->getContents($buildQuery, true);
        $contentDetails = (array) $readable->items[0]->contentDetails;
        $stats = (array) $readable->items[0]->statistics;
        $details = array_merge($contentDetails, $stats);

        if (is_array($details)) {
          if (!$type) {
            $details['duration'] = $this->convertYouTubeTime($details['duration']);
            return $details;
          } else {
            switch ($type) {
              case 'all':
                if (is_object($readable)) {
                  return $readable;
                }
                break;
              case 'duration':
                $duration = $details['duration'];
                if (!empty($duration)) {
                  $time = $this->convertYouTubeTime($duration);
                  if (is_numeric($time)) {
                    return $time;
                  }
                }
                break;
              case 'definition':
                $quality = $details['definition'];
                if (!empty($quality)) {
                  return $quality;
                }
                break;
              case 'views':
                $views = $details['viewCount'];
                if (is_numeric($views)) {
                  return $views;
                }
                break;
              case 'likes':
                $likes = $details['likeCount'];
                if (is_numeric($likes)) {
                  return $likes;
                }
                break;
              case 'dlikes':
                $dlikes = $details['dislikeCount'];
                if (is_numeric($dlikes)) {
                  return $dlikes;
                }
                break;
              case 'favs':
                $favs = $details['favoriteCount'];
                if (is_numeric($favs)) {
                  return $favs;
                }
                break;
              case 'comments':
                $comments = $details['commentCount'];
                if (is_numeric($comments)) {
                  return $comments;
                }
                break;
              default:
                # code...
                break;
            }
          }
        } else {
          throw new Exception('Given data is invalid array');
        }
      } else {
        throw new Exception('Invalid video id');
      }
    } catch (Exception $e) {
      echo $e;
    }
  }

  public function getDuration($id) {
    return $this->getContentDetails($id, 'duration');
  }

  public function getViews($id) {
    return $this->getContentDetails($id, 'views');
  }

  public function getLikes($id) {
    return $this->getContentDetails($id, 'likes');
  }

  /**
  * Get YouTube channel ID using channel Name
  * @param : { string } { $channel_name } { name of the channel }
  * @return : { integer } { $channel_id } { Id of channel using given name }
  */

  public function getChannelIdByName( $channelName ) {
    try {
      $buildQuery = $this->channelsBaseUrl . '?forUsername=' . $channelName;
      $buildQuery .= '&part=id&key=' . $this->apiKey;

      $readable = $this->getContents($buildQuery, true);
      if (!$readable) {
        throw new Exception('Something went wrong trying to fetch channel id');
      }
      return $readable->items[0]->id;
    } catch (Exception $e) {
      echo $e;
    }
  }

  /**
  * Get YouTube channel ID using video URL
  * @param : { string } { $url } { url of any video of that channel }
  * @return : { integer } { $channel_id } { id of youtube channel }
  */

  public function getChannelIdByVideoUrl($url) {
    try {
      $buildQuery = $this->videosBaseUrl . '?id=' . $this->getIdByUrl($url);
      $buildQuery .= '&part=snippet&key=' . $this->apiKey;

      $readable = $this->getContents($buildQuery, true);
      if (!$readable) {
        throw new Exception('Something went wrong trying to fetch channel id by video');
      }

      return isset($readable->items[0]->snippet->channelId) ? $readable->items[0]->snippet->channelId : false;
    } catch (Exception $e) {
      echo $e;
    }
  }

  /** 
  * Extracts YouTube video id from URL
  * @param : { string } { $url } { link to youtube video }
  */

  public function getIdByUrl($url) {
    $url_string = parse_url($url, PHP_URL_QUERY);
    parse_str($url_string, $args);
    return isset($args['v']) ? $args['v'] : false;
  }

  /**
  * Converts YouTube time format (PT3M20S) to seconds
  * @param : { string } { $defaultTime } { youtube time stamp }
  */

  private function convertYouTubeTime($defaultTime) {
    preg_match_all('!\d+!', $defaultTime, $matches);
    $elems = $matches[0];
    $items = count($elems);
    switch ($items) {
      case 1:
        $mode = 's'; // secs
        break;
      case 2:
        $mode = 'm'; // mins
        break;
      case 3:
        $mode = 'h'; // hours
        break;
      case 4:
        $mode = 'd'; // days
        break;
      case 5:
        $mode = 'w'; // weeks
        break;
      default:
        # code...
        break;
    }

    switch ($mode) {
      case 's':
        $total = $elems[0];
        break;
      case 'm':
        $mins = $elems[0] * 60;
        $total = $mins + $elems[1];
        break;
      case 'h':
        $hours = $elems[0] * 3600;
        $mins = $elems[1] * 60;
        $total = $hours + $mins + $elems[2];
        break;
      case 'd':
        $days = $elems[0] * 86400;
        $hours = $elems[1] * 3600;
        $mins = $elems[2] * 60;
        $total = $days + $hours + $mins + $elems[3];
        break;
      
      default:
        return false;
        break;
    }
    return $total;
  }

  private function decode($data) {
    return json_decode($data);
  }

  private function getContents($url, $decode = false) {
    try {
      if (function_exists('curl_version')) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $queryResults = curl_exec($ch);
      curl_close($ch);  
    } else {
      $queryResults = file_get_contents($url);
    }
    
    if ($decode) {
      $queryResults = $this->decode($queryResults);
    }
    return $queryResults;
    } catch (Exception $e) {
      echo $a;
    }
  }

  private function cleanupQuery($query) {
    return str_replace(' ', '+', $query);
  }
}
