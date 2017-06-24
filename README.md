A flexible API wrapper class to help you interact with YouTube API v3

## Installation
- Download latest release and extract contents.
- Include YouTube.class.php in your project.
- Have fun

## Usage
#### search
Search YouTube videos against a query
```
$youtube = new YouTube('your_youtube_api_key');

$parameters = array();
$parameters['query'] = 'Jon Snow';
$parameters['videosLimit'] = 5;
$parameters['token'] = 'your_page_token_if_you_are_accessing_second_or_higher_page';
$parameters['sort'] = 'relevance'; // Possible options are: viewCount, rating, date, relevance

$youtube->search($paramters);
```
#### searchChannels
Search YouTube channels against a query
```
$youtube = new YouTube('your_youtube_api_key');

$parameters = array();
$parameters['query'] = 'WWE';
$parameters['videosLimit'] = 5;
$parameters['token'] = 'your_page_token_if_you_are_accessing_second_or_higher_page';

$results = $youtube->searchChannels($paramters);
```
#### getRelatedVideos
Get related videos for given video
```
$youtube = new YouTube('your_youtube_api_key');

$parameters = array();
$parameters['videoId'] = 'youtube_vide_id';
$parameters['videosLimit'] = 5;
$parameters['token'] = 'your_page_token_if_you_are_accessing_second_or_higher_page';

$results = $youtube->getRelatedVideos($paramters);
```
#### getDuration
Get duration of a video
```
$youtube = new YouTube('your_youtube_api_key');
$duration = $youtube->getDuration('video_id');
```
#### getViews
Get views count of a video
```
$youtube = new YouTube('your_youtube_api_key');
$views = $youtube->getViews('video_id');
```
#### getLikes
Get likes count of a video
```
$youtube = new YouTube('your_youtube_api_key');
$likes = $youtube->getLikes('video_id');
```
#### getChannelIdByName
Get channel ID by channel name
```
$youtube = new YouTube('your_youtube_api_key');
$channelId = $youtube->getChannelIdByName('channel_name');
```
#### getChannelIdByVideoUrl
Get channel ID by video URL
```
$youtube = new YouTube('your_youtube_api_key');
$channelId = $youtube->getChannelIdByVideoUrl('video_url');
```
#### getIdByUrl
Extract video ID from URL
```
$youtube = new YouTube('your_youtube_api_key');
$videoId = $youtube->getIdByUrl('video_url');
```
