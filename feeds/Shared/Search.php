<?php 
include "../../Config.php";
if(isset($_GET["q"]) ||  str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.0") ||  str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.1")||  str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.2")||  str_contains($_SERVER["HTTP_USER_AGENT"], "Apple")){
if(isset($_SERVER[$customAPIKeyHeader])){ $APIkey = $_SERVER[$customAPIKeyHeader];}else{exit;}
$getsortby = (isset($_GET["orderby"]))? $_GET["orderby"] : "relevance";
$curlConnectionInitialization = curl_init("https://" . $APIurl . "/youtube/v3/search?part=snippet&maxResults=" . $MaxCount . "&q=" . preg_replace('/\s+/', '', $_GET["q"]) ."&type=video&orderby=$getsortby&key=" . $APIkey);
curl_setopt($curlConnectionInitialization, CURLOPT_HEADER, 0);
curl_setopt($curlConnectionInitialization, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curlConnectionInitialization);

$decodeResponce = json_decode($response, true);
$kindResponse = json_decode($response, false)->kind;
if($kindResponse == "youtube#searchListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.0") || $kindResponse == "youtube#searchListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.1")|| $kindResponse == "youtube#searchListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.2")|| $kindResponse == "youtube#searchListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "Apple") ){
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
	$channelname = htmlspecialchars($decodeResponce['items'][$i]['snippet']['channelTitle']);
	$description = htmlspecialchars($statisticResponse['items'][$i]['snippet']['description']);
	$videoname = htmlspecialchars($decodeResponce['items'][$i]['snippet']['title']);
	$publishDate = $decodeResponce['items'][$i]['snippet']['publishedAt'];
	$publishDateAS = rtrim($decodeResponce['items'][$i]['snippet']['publishedAt'], 'Z') . ".000Z";
	
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
	$dislikes = isHigherThanZero(($views - ($likes * 16)) / 48) ? ($views - ($likes * 16)) / 48 : 0;
	//$videoDuration = strtotime($statisticResponse['items'][0]['contentDetails']['duration']);
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
	$entryClassicTube = <<<Entry
	<entry gd:etag="$etag">
    <id>tag:youtube.com,2008:video:$videoID</id>
    <published>$publishDate</published>
    <updated>$publishDate</updated>
    <category scheme="http://schemas.google.com/g/2005#kind" term="http://$baseURL/schemas/2007#video" />
    <category scheme="http://$baseURL/schemas/2007/categories.cat" term="Music" label="Music" />
    <title>$videoname</title>
    <content type="video/mp4" src="http://$baseURL/videoDump/GetVideo.php?videoId=$videoID" />
    <link rel="alternate" type="text/html" href="https://www.youtube.com/watch?v=$videoID&amp;feature=youtube_gdata" />
    <link rel="http://$baseURL/schemas/2007#video.complaints" type="application/atom+xml" href="https://$baseURL/feeds/api/videos/$videoID/complaints" />
    <link rel="http://$baseURL/schemas/2007#video.related" type="application/atom+xml" href="https://$baseURL/feeds/api/videos/$videoID/related" />
    <link rel="http://$baseURL/schemas/2007#video.captionTracks" type="application/atom+xml" href="https://$baseURL/feeds/api/videos/$videoID/captions" yt:hasEntries="false" />
    <link rel="http://$baseURL/schemas/2007#mobile" type="text/html" href="http://m.youtube.com/details?v=$videoID" />
    <link rel="http://$baseURL/schemas/2007#uploader" type="application/atom+xml" href="https://$baseURL/feeds/api/users/UCealgY8FrRPdAaCN8UtkuIQ" />
    <link rel="self" type="application/atom+xml" href="http://$baseURL/videoDump/GetVideo.php?videoId=$videoID" />
    <author>
      <name>$channelname</name>
      <uri>https://www.google.com/feeds/api/users/$channelname</uri>
      <yt:userId>$channelId</yt:userId>
    </author>

    <yt:accessControl action="comment" permission="allowed" />
    <yt:accessControl action="commentVote" permission="allowed" />
    <yt:accessControl action="videoRespond" permission="denied" />
    <yt:accessControl action="rate" permission="allowed" />
    <yt:accessControl action="embed" permission="allowed" />
    <yt:accessControl action="list" permission="allowed" />
    <yt:accessControl action="monetize" permission="denied" />
    <yt:accessControl action="autoPlay" permission="allowed" />
    <yt:accessControl action="syndicate" permission="allowed" />
    <gd:comments><gd:feedLink href='http://$baseURL/feeds/AppStore/ASGetComments.php?videoId=$videoID'/></gd:comments>
    <yt:statistics favoriteCount="$favoriteCount" viewCount="$views" />
<gd:rating average="3" max="3" min="3" numRaters="1" rel="http://schemas.google.com/g/2005#overall" />
    <yt:rating numDislikes="$dislikes" numLikes="$likes" />
<yt:hd />
    <media:group>
      <media:category label="Music" scheme="http://$baseURL/schemas/2007/categories.cat">Music</media:category>
      <media:content url="http://$baseURL/videoDump/GetVideo.php?videoId=$videoID" type="video/mp4" medium="video" isDefault="true" expression="full" duration="$durationInSeconds" yt:format="3" />
      <media:content url="https://$baseURL/$videoID.3gpp" type="video/3gpp" medium="video" expression="full" duration="$durationInSeconds" yt:format="2" />
      <media:content url="http://$baseURL/videoDump/GetVideo.php?videoId=$videoID" type="video/mp4" medium="video" expression="full" duration="$durationInSeconds" yt:format="8" />
      <media:content url="https://$baseURL/$videoID.3gpp" type="video/3gpp" medium="video" expression="full" duration="$durationInSeconds" yt:format="9" />
      <media:credit role="uploader" scheme="urn:youtube" yt:display="$channelname">$channelname</media:credit>
      <media:description type="plain">$description</media:description>
      <media:keywords>keywords</media:keywords>
      <media:license type="text/html" href="http://www.youtube.com/t/terms">youtube</media:license>
      <media:player url="https://www.youtube.com/watch?v=$videoID&amp;feature=youtube_gdata_player" />
      <media:thumbnail url="$defaultTHURL" height="90" width="120" yt:name="default" />
			<media:thumbnail url="$mediumTHURL" height="180" width="320" yt:name="mqdefault" />
      <media:thumbnail url="$highTHURL" height="360" width="480" yt:name="hqdefault" />
      <media:thumbnail url="$highTHURL" height="480" width="640" yt:name="sddefault" />
      <media:thumbnail url="$highTHURL" height="720" width="1280" yt:name="naxresdefault" />
      <media:title type="plain">$videoname</media:title>
      <yt:aspectRatio>widescreen</yt:aspectRatio>
      <yt:duration seconds="$durationInSeconds" />
      <yt:uploaded>$publishDate</yt:uploaded>
      <yt:uploaderId>$channelId</yt:uploaderId>
      <yt:videoid>$videoID</yt:videoid>
    </media:group>
  </entry>
Entry;
$entryASYoutube = <<<Entry
	<entry gd:etag='W/&quot;YDwqeyM.&quot;'>
		<id>tag:youtube.com,2008:playlist:$videoID:$videoID</id>
		<published>$publishDateAS</published>
		<updated>$publishDateAS</updated>
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
			<yt:uploaded>$publishDateAS</yt:uploaded>
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
    if(str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios")){
      $entries = $entries . $entryASYoutube;
    }
    else{
	$entries = $entries . $entryClassicTube;
    }
	}
$youtubeXML = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:gd="http://schemas.google.com/g/2005" xmlns:openSearch="http://a9.com/-/spec/opensearch/1.1/" xmlns:yt="http://$baseURL/schemas/2007" xmlns:media="http://search.yahoo.com/mrss/" gd:etag="">
  <id>http://$baseURL/feeds/api/standardfeeds/US/recently_featured</id>
  <category scheme="http://schemas.google.com/g/2005#kind" term="http://$baseURL/schemas/2007/#video" />
<title>Spotlight Videos</title>  <logo>http://www.gstatic.com/youtube/img/logo.png</logo>
  <link rel="http://schemas.google.com/g/2005#feed" type="application/atom+xml" href="https://$baseURL/feeds/api/standardfeeds/US/recently_featured" />
  <link rel="http://schemas.google.com/g/2005#batch" type="application/atom+xml" href="https://$baseURL/feeds/api/standardfeeds/US/recently_featured/batch" />
  <link rel="self" type="application/atom+xml" href="https://$baseURL/feeds/api/standardfeeds/US/recently_featured?start-index=1&amp;max-results=25&amp;format=2,3,8,9&amp;fields=openSearch:totalResults,openSearch:startIndex,openSearch:itemsPerPage,link%5B@rel=&#039;http://schemas.google.com/g/2005%23batch&#039;%5D,entry(id,title,updated,published,yt:rating,link%5B@rel=&#039;edit&#039;%20or%20@rel=&#039;https://$baseURL/schemas/2007%23video.ratings&#039;%5D,yt:statistics(@viewCount),batch:status,yt:accessControl%5B@action=&#039;list&#039;%5D,media:group(media:thumbnail,media:content%5B@yt:format=&#039;2&#039;%20or%20@yt:format=&#039;3&#039;%20or%20@yt:format=&#039;8&#039;%20or%20@yt:format=&#039;9&#039;%5D(@yt:format,@url,@duration),media:category,media:player,media:description,media:keywords,media:rating,yt:videoid,media:credit,yt:private),app:control,gd:comments)" />
  <!-- <link rel="service" type="application/atomsvc+xml" href="https://$baseURL/feeds/api/standardfeeds/US/recently_featured?alt=atom-service" /> -->
  <author>
    <name>Credits:TubeFixer</name>
    <uri>https://mali357.gay/</uri>
  </author>
<openSearch:totalResults>1</openSearch:totalResults>
  <openSearch:itemsPerPage>25</openSearch:itemsPerPage>
  <openSearch:startIndex>1</openSearch:startIndex>
  $entries
</feed>
<res>$response</res>
XML;
die($youtubeXML);
}
else{
	
die("An internal server error has occured! If there is a new version of the server please update to the latest version! Error: " . $response);
}
curl_close($curlConnectionInitialization);	
}

function isHigherThanZero($num){
	if($num > 0){
	return true;
	}
   return false;
}

function getVideoDetailsJson($videoId, $APIurl, $key){
$curlConnectionInitialization = curl_init("https://" . $APIurl . "/youtube/v3/videos?part=statistics,contentDetails,snippet&id=". $videoId ."&key=" . $key);
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