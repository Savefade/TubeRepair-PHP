<?php 
include "../../Config.php";
if(isset($_GET["category"]) && isset($_GET["region"])){
if(isset($_SERVER[$customAPIKeyHeader]) && strlen($_GET["region"]) == 2){ $APIkey = $_SERVER[$customAPIKeyHeader];}else{exit;}
$curlConnectionInitialization = curl_init("https://" . $APIurl . "/youtube/v3/search?part=snippet&maxResults=" . $MaxCount . "&q=" . preg_replace('/\s+/', '', $_GET["category"]) ."&type=video&regionCode=" . $_GET["region"] . "&key=" . $APIkey);
curl_setopt($curlConnectionInitialization, CURLOPT_HEADER, 0);
curl_setopt($curlConnectionInitialization, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curlConnectionInitialization);

$decodeResponce = json_decode($response, true);
$kindResponse = json_decode($response, false)->kind;
if($kindResponse == "youtube#searchListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.0") || $kindResponse == "youtube#searchListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.1")|| $kindResponse == "youtube#searchListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.2") || $forceSupport){
	$maxResultsFromYT = $decodeResponce['pageInfo']['resultsPerPage'];
	$entries = "";
	$videoIdsFromResult = "";
	for($i = 0; $i<$maxResultsFromYT; $i++){
	$videoID = $decodeResponce['items'][$i]['id']['videoId'];
    $videoIdsFromResult = empty($videoIdsFromResult) ? $videoID : $videoIdsFromResult . "," . $videoID ;
	}
	$statisticResponse = getVideoDetailsJson($videoIdsFromResult, $APIurl, $APIkey);
	for($i = 0; $i<$maxResultsFromYT; $i++){
	$videoID = $decodeResponce['items'][$i]['id']['videoId'];
	$channelname = $decodeResponce['items'][$i]['snippet']['channelTitle'];
	//$description = $decodeResponce['items'][$i]['snippet']['description'];
	$description = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $decodeResponce['items'][$i]['snippet']['description']);
	//$description = "Not Supported!";
	$videoname = $decodeResponce['items'][$i]['snippet']['title'];
	$publishDate = 	rtrim($decodeResponce['items'][$i]['snippet']['publishedAt'], 'Z') . ".000Z";
	
	$likes = 0;
	$views = 0;
	$commentCount = 0;
	$favoriteCount = 0;
	$dislikes = 0;
	
	if(isset($statisticResponse['items'][$i]['statistics']['viewCount']))
	$views = $statisticResponse['items'][$i]['statistics']['viewCount'];
	if(isset($statisticResponse['items'][$i]['statistics']['likeCount']))
	$likes = $statisticResponse['items'][$i]['statistics']['likeCount'];
    if(isset($statisticResponse['items'][$i]['statistics']['commentCount']))
	$commentCount = $statisticResponse['items'][$i]['statistics']['commentCount'];
    if(isset($statisticResponse['items'][$i]['statistics']['favoriteCount']))
	$favoriteCount = $statisticResponse['items'][$i]['statistics']['favoriteCount'];
    if(isset($statisticResponse['items'][$i]['statistics']['likeCount']) && isset($statisticResponse['items'][$i]['statistics']['viewCount']))
	$dislikes = 0 < ($views - ($likes * 16)) / 48 ? ($views - ($likes * 16)) / 48 : 0;
	//$durationIn = new DateInterval($videoDuration);
	//$durationInSeconds = $durationIn->s + $durationIn->i * 60 + $durationIn->h * 3600;
	$videoDuration = $statisticResponse['items'][$i]['contentDetails']['duration'];
    $YTRtoDI = new DateInterval($videoDuration);
    $durationInSeconds = ($YTRtoDI->d * 86400) + ($YTRtoDI->h * 3600) + ($YTRtoDI->i * 60) + $YTRtoDI->s;
	
	$channelId = $decodeResponce['items'][$i]['snippet']['channelId'];
	$etag = $decodeResponce['items'][$i]['etag'];
	$defaultTHURL = $decodeResponce['items'][$i]['snippet']['thumbnails']['default']['url'];
	$mediumTHURL = $decodeResponce['items'][$i]['snippet']['thumbnails']['medium']['url'];
	$highTHURL = $decodeResponce['items'][$i]['snippet']['thumbnails']['high']['url'];
$entry = <<<Entry
	<entry gd:etag='W/&quot;YDwqeyM.&quot;'>
		<id>tag:youtube.com,2008:playlist:$videoID:$videoID</id>
		<published>$publishDate</published>
		<updated>$publishDate</updated>
		<category scheme='http://schemas.google.com/g/2005#kind' term='http://$baseURL/schemas/1970#video'/>
		<category scheme='http://$baseURL/schemas/1970/categories.cat' term='Howto' label='Howto &amp; Style'/>
		<title>$videoname</title>
		<content type='application/x-shockwave-flash' src='http://www.youtube.com/v/$videoID?version=3&amp;f=playlists&amp;app=youtube_gdata'/>
		<link rel='alternate' type='text/html' href='http://www.youtube.com/watch?v=$videoID&amp;feature=youtube_gdata'/>
		<link rel='http://$baseURL/schemas/1970#video.related' type='application/atom+xml' href='http://$baseURL/feeds/api/videos/$videoID/related?v=2'/>
		<link rel='http://$baseURL/schemas/1970#mobile' type='text/html' href='http://m.youtube.com/details?v=$videoID'/>
		<link rel='http://$baseURL/schemas/1970#uploader' type='application/atom+xml' href='http://$baseURL/feeds/api/users/$channelId?v=2'/>
		<link rel='related' type='application/atom+xml' href='http://$baseURL/feeds/api/videos/$videoID?v=2'/>
		<link rel='self' type='application/atom+xml' href='http://$baseURL/feeds/api/playlists/8E2186857EE27746/PLyl9mKRbpNIpJC5B8qpcgKX8v8NI62Jho?v=2'/>
		<author>
			<name>$channelname</name>
			<uri>http://$baseURL/feeds/api/users/$channelId</uri>
			<yt:userId>$channelId</yt:userId>
		</author>
		<yt:accessControl action='comment' permission='allowed'/>
		<yt:accessControl action='commentVote' permission='allowed'/>
		<yt:accessControl action='videoRespond' permission='moderated'/>
		<yt:accessControl action='rate' permission='allowed'/>
		<yt:accessControl action='embed' permission='allowed'/>
		<yt:accessControl action='list' permission='allowed'/>
		<yt:accessControl action='autoPlay' permission='allowed'/>
		<yt:accessControl action='syndicate' permission='allowed'/>
		<gd:comments>
			<gd:feedLink rel='http://$baseURL/schemas/1970#comments' href='http://$baseURL/feeds/api/videos/$videoID/comments?v=2' countHint='5'/>
		</gd:comments>
		<yt:location>Paris ,FR</yt:location>
		<media:group>
			<media:category label='Howto &amp; Style' scheme='http://$baseURL/schemas/1970/categories.cat'>Howto</media:category>
			<media:content url='http://www.youtube.com/v/$videoID?version=3&amp;f=playlists&amp;app=youtube_gdata' type='application/x-shockwave-flash' medium='video' isDefault='true' expression='full' duration='0' yt:format='5'/>
			<media:content url='http://$baseURL/$videoID.3gpp" type="video/3gpp' type='video/3gpp' medium='video' expression='full' duration='0' yt:format='1'/>
			<media:content url='http://$baseURL/$videoID.3gpp" type="video/3gpp' type='video/3gpp' medium='video' expression='full' duration='0' yt:format='6'/>
			<media:credit role='uploader' scheme='urn:youtube' yt:display='$channelname' yt:type='partner'>$channelId</media:credit>
			<media:description type='plain'>$description</media:description>
			<media:keywords/>
			<media:license type='text/html' href='http://www.youtube.com/t/terms'>youtube</media:license>
			<media:player url='http://www.youtube.com/watch?v=$videoID&amp;feature=youtube_gdata_player'/>
			<media:thumbnail url='$defaultTHURL' height='90' width='120' time='00:00:00.000' yt:name='default'/>
			<media:thumbnail url='$mediumTHURL' height='180' width='320' yt:name='mqdefault'/>
			<media:thumbnail url='$highTHURL' height='360' width='480' yt:name='hqdefault'/>
			<media:thumbnail url='$defaultTHURL' height='90' width='120' time='00:00:00.000' yt:name='start'/>
			<media:thumbnail url='$defaultTHURL' height='90' width='120' time='00:00:00.000' yt:name='middle'/>
			<media:thumbnail url='$defaultTHURL' height='90' width='120' time='00:00:00.000' yt:name='end'/>
			<media:content url="http://$baseURL/videoDump/GetVideo.php?videoId=$videoID" type="video/mp4" medium="video" isDefault="true" expression="full" duration="0" yt:format="3" />
            <media:content url="http://$baseURL/$videoID.3gpp" type="video/3gpp" medium="video" expression="full" duration="0" yt:format="2" />
            <media:content url="http://$baseURL/videoDump/GetVideo.php?videoId=$videoID" type="video/mp4" medium="video" expression="full" duration="0" yt:format="8" />
            <media:content url="http://$baseURL/$videoID.3gpp" type="video/3gpp" medium="video" expression="full" duration="0" yt:format="9" />
			<media:title type='plain'>$videoname</media:title>
			<yt:duration seconds='$durationInSeconds'/>
			<yt:uploaded>$publishDate</yt:uploaded>
			<yt:uploaderId>$channelId</yt:uploaderId>
			<yt:videoid>$videoID</yt:videoid>
		</media:group>
		<gd:rating average='0' max='0' min='0' numRaters='0' rel='http://schemas.google.com/g/2005#overall'/>
		<yt:recorded>1970-01-01</yt:recorded>
		<yt:statistics favoriteCount='$favoriteCount' viewCount='$views'/>
		<yt:rating numDislikes='$dislikes' numLikes='$likes'/>
		<yt:position>1</yt:position>
	</entry>
Entry;
   $entries = $entries . $entry;
	}
$youtubeXML = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<feed
	xmlns='http://www.w3.org/2005/Atom'
	xmlns:media='http://search.yahoo.com/mrss/'
	xmlns:openSearch='http://a9.com/-/spec/opensearch/1.1/'
	xmlns:gd='http://schemas.google.com/g/2005'
	xmlns:yt='http://$baseURL/schemas/1970' gd:etag='W/&quot;D0UHSX47eCp7I2A9WhJWF08.&quot;'>
	<id>tag:youtube.com,2008:playlist:8E2186857EE27746</id>
	<updated>2012-08-23T12:33:58.000Z</updated>
	<category scheme='http://schemas.google.com/g/2005#kind' term='http://$baseURL/schemas/1970#playlist'/>
	<title></title>
	<subtitle></subtitle>
	<logo>http://www.gstatic.com/youtube/img/logo.png</logo>
	<link rel='alternate' type='text/html' href='http://www.youtube.com/playlist?list=PL8E2186857EE27746'/>
	<link rel='http://schemas.google.com/g/2005#feed' type='application/atom+xml' href='http://$baseURL/feeds/api/playlists/8E2186857EE27746?v=2'/>
	<link rel='http://schemas.google.com/g/2005#batch' type='application/atom+xml' href='http://$baseURL/feeds/api/playlists/8E2186857EE27746/batch?v=2'/>
	<link rel='self' type='application/atom+xml' href='http://$baseURL/feeds/api/playlists/8E2186857EE27746?start-index=1&amp;max-results=25&amp;v=2'/>
	<link rel='service' type='application/atomsvc+xml' href='http://$baseURL/feeds/api/playlists/8E2186857EE27746?alt=atom-service&amp;v=2'/>
	<author>
		<name>$channelname</name>
		<uri>http://$baseURL/feeds/api/users/$channelId</uri>
		<yt:userId>$channelId</yt:userId>
	</author>
	<generator version='2.1' uri='http://$baseURL'>YouTube data API</generator>
<openSearch:totalResults>$maxResultsFromYT</openSearch:totalResults>
  <openSearch:itemsPerPage>$maxResultsFromYT</openSearch:itemsPerPage>
  <openSearch:startIndex>1</openSearch:startIndex>
  $entries
  <api>App Store</api>
  <debug>$response</debug>
</feed>
XML;
die($youtubeXML);
}
else{
	
die("An internal server error has occured! If there is a new version of the server please update to the latest version! Error: " . $response);
}
curl_close($curlConnectionInitialization);	
}

function getVideoDetailsJson($videoId, $APIurl, $key){
$curlConnectionInitialization = curl_init("https://" . $APIurl . "/youtube/v3/videos?part=statistics,contentDetails&id=". $videoId ."&key=" . $key);
curl_setopt($curlConnectionInitialization, CURLOPT_HEADER, 0);
curl_setopt($curlConnectionInitialization, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curlConnectionInitialization);
if(curl_error($curlConnectionInitialization)) {
  die(curl_error($curlConnectionInitialization));
}
$decodeResponce = json_decode($response, true);
if(!isset(json_decode($response, false)->kind)){
	if($decodeResponce["error"]["errors"][0]["reason"] ==  "badRequest"){
		header("HTTP/1.0 403 Forbidden");
	    die($response);
	}
	if($decodeResponce["error"]["errors"][0]["reason"] ==  "quotaExceeded"){
		header("HTTP/1.1 401 Unauthorized");
	    die($response);
	}
}
return $decodeResponce;
} 