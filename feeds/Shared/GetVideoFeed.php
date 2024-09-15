<?php
include "../../Config.php";
if(isset($_GET["id"])){
if(isset($_SERVER[$customAPIKeyHeader])){ $APIkey = $_SERVER[$customAPIKeyHeader];}else{die("api key not set");}
$curlConnectionInitialization = curl_init("https://" . $APIurl . "/youtube/v3/videos?part=snippet&id=" . $_GET["id"] . "&type=video&key=" . $APIkey);
curl_setopt($curlConnectionInitialization, CURLOPT_HEADER, 0);
curl_setopt($curlConnectionInitialization, CURLOPT_RETURNTRANSFER, true);

$response = sanitizeResponse(curl_exec($curlConnectionInitialization));
$decodeResponce = json_decode($response, true);
if(isset($decodeResponce["error"]["errors"][0]["reason"])){
	if($decodeResponce["error"]["errors"][0]["reason"] ==  "badRequest"){
		header("HTTP/1.0 403 Forbidden");
	}
	if($decodeResponce["error"]["errors"][0]["reason"] ==  "quotaExceeded"){
		header("HTTP/1.1 401 Unauthorized");
	}
	die($response);
}
$kindResponse = json_decode($response, false)->kind;
if($kindResponse == "youtube#videoListResponse"){
	$maxResultsFromYT = $decodeResponce['pageInfo']['resultsPerPage'];
	$entries = "";
	$statisticResponse = getVideoDetailsJson($_GET["id"], $APIurl, $APIkey);
	$videoID = $decodeResponce['items'][0]['id'];
	$channelname = $decodeResponce['items'][0]['snippet']['channelTitle'];
	$description = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $decodeResponce['items'][0]['snippet']['description']);
	$videoname = $decodeResponce['items'][0]['snippet']['title'];
	$publishDate = 	rtrim($decodeResponce['items'][0]['snippet']['publishedAt'], 'Z') . ".000Z";
	$updatedDate = date("Y-m-d\TH:i:s\Z");

	$likes = 0;
	$views = 0;
	$commentCount = 0;
	$favoriteCount = 0;
	$dislikes = 0;
	
	$views = $statisticResponse['items'][0]['statistics']['viewCount'];
	if(isset($statisticResponse['items'][0]['statistics']['likeCount']))
	$likes = $statisticResponse['items'][0]['statistics']['likeCount'];
    if(isset($statisticResponse['items'][0]['statistics']['commentCount']))
	$commentCount = $statisticResponse['items'][0]['statistics']['commentCount'];
    if(isset($statisticResponse['items'][0]['statistics']['favoriteCount']))
	$favoriteCount = $statisticResponse['items'][0]['statistics']['favoriteCount'];
    if(isset($statisticResponse['items'][0]['statistics']['likeCount']) && isset($statisticResponse['items'][0]['statistics']['viewCount']))
	$dislikes = ($views - ($likes * 16)) / 48;
	$videoDuration = $statisticResponse['items'][0]['contentDetails']['duration'];
    $YTRtoDI = new DateInterval($videoDuration);
    $durationInSeconds = ($YTRtoDI->d * 86400) + ($YTRtoDI->h * 3600) + ($YTRtoDI->i * 60) + $YTRtoDI->s;
	
	$channelId = $decodeResponce['items'][0]['snippet']['channelId'];
	$etag = $decodeResponce['items'][0]['etag'];
	$defaultTHURL = $decodeResponce['items'][0]['snippet']['thumbnails']['default']['url'];
	$mediumTHURL = $decodeResponce['items'][0]['snippet']['thumbnails']['medium']['url'];
	$highTHURL = $decodeResponce['items'][0]['snippet']['thumbnails']['high']['url'];
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
    if(str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios")){
      $entries = $entryASYoutube;
    }
    else{
	$entries = $entryClassicTube;
    }
}
	// Generate the Atom feed
	echo <<<XML
<?xml version='1.0' encoding='UTF-8'?>
	$entries
XML;
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
?>
