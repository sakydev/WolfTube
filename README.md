A flexible API wrapper class to help you interact with YouTube API v3

# Usage
#### search
Search YouTube videos against a query
```
$parameters = array();
$parameters['query'] = 'Jon Snow';
$parameters['videosLimit'] = 5;
$parameters['token'] = 'your_page_token_if_you_are_accessing_second_or_higher_page';
$parameters['sort'] = 'relevance'; // Possible options are: viewCount, rating, date, relevance
$youtube->search($paramters);
```
#### searchChannels
#### getRelatedVideos
#### getDuration
#### getViews
#### getLikes
#### getChannelIdByName
#### getChannelIdByVideoUrl
#### getIdByUrl
