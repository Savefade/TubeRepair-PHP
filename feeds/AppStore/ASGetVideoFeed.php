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
if($kindResponse == "youtube#videoListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.0") || $kindResponse == "youtube#videoListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.1")|| $kindResponse == "youtube#videoListResponse" && str_contains($_SERVER["HTTP_USER_AGENT"], "com.google.ios.youtube/1.2") || str_contains($_SERVER["HTTP_USER_AGENT"], "Android") || $forceSupport){
	$maxResultsFromYT = $decodeResponce['pageInfo']['resultsPerPage'];
	$entries = "";
	$videoIdsFromResult = "";
	for($i = 0; $i<$maxResultsFromYT; $i++){
	$videoID = $decodeResponce['items'][$i]['id'];
    $videoIdsFromResult = empty($videoIdsFromResult) ? $videoID : $videoIdsFromResult . "," . $videoID ;
	}
	$statisticResponse = getVideoDetailsJson($videoIdsFromResult, $APIurl, $APIkey);
	for($i = 0; $i<$maxResultsFromYT; $i++){
	$videoID = $decodeResponce['items'][$i]['id'];
	$channelname = $decodeResponce['items'][$i]['snippet']['channelTitle'];
	$description = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $decodeResponce['items'][$i]['snippet']['description']);
	$videoname = $decodeResponce['items'][$i]['snippet']['title'];
	$publishDate = 	rtrim($decodeResponce['items'][$i]['snippet']['publishedAt'], 'Z') . ".000Z";
	$updatedDate = date("Y-m-d\TH:i:s\Z");

	$likes = 0;
	$views = 0;
	$commentCount = 0;
	$favoriteCount = 0;
	$dislikes = 0;
	
	$views = $statisticResponse['items'][$i]['statistics']['viewCount'];
	if(isset($statisticResponse['items'][$i]['statistics']['likeCount']))
	$likes = $statisticResponse['items'][$i]['statistics']['likeCount'];
    if(isset($statisticResponse['items'][$i]['statistics']['commentCount']))
	$commentCount = $statisticResponse['items'][$i]['statistics']['commentCount'];
    if(isset($statisticResponse['items'][$i]['statistics']['favoriteCount']))
	$favoriteCount = $statisticResponse['items'][$i]['statistics']['favoriteCount'];
    if(isset($statisticResponse['items'][$i]['statistics']['likeCount']) && isset($statisticResponse['items'][$i]['statistics']['viewCount']))
	$dislikes = ($views - ($likes * 16)) / 48;
	$videoDuration = $statisticResponse['items'][$i]['contentDetails']['duration'];
    $YTRtoDI = new DateInterval($videoDuration);
    $durationInSeconds = ($YTRtoDI->d * 86400) + ($YTRtoDI->h * 3600) + ($YTRtoDI->i * 60) + $YTRtoDI->s;
	
	$channelId = $decodeResponce['items'][$i]['snippet']['channelId'];
	$etag = $decodeResponce['items'][$i]['etag'];
	$defaultTHURL = $decodeResponce['items'][$i]['snippet']['thumbnails']['default']['url'];
	$mediumTHURL = $decodeResponce['items'][$i]['snippet']['thumbnails']['medium']['url'];
	$highTHURL = $decodeResponce['items'][$i]['snippet']['thumbnails']['high']['url'];
	$entryiOS = <<<Entry
	<entry
	xmlns='http://www.w3.org/2005/Atom'
	xmlns:media='http://search.yahoo.com/mrss/'
	xmlns:gd='http://schemas.google.com/g/2005'
	xmlns:yt='http://192.168.2.197/schemas/2007' gd:etag='W/&quot;YDwqeyM.&quot;'>
		<id>tag:youtube.com,2008:playlist:$videoID:$videoID</id>
		<published>$publishDate</published>
		<updated>$updatedDate</updated>
		<category scheme='http://schemas.google.com/g/2005#kind' term='http://$baseURL/schemas/1970#video'/>
		<category scheme='http://$baseURL/schemas/1970/categories.cat' term='Howto' label='Howto &amp; Style'/>
		<title>$videoname</title>
		<content type='application/x-shockwave-flash' src='http://www.youtube.com/v/$videoID?version=3&amp;f=playlists&amp;app=youtube_gdata'/>
		<link rel='alternate' type='text/html' href='http://www.youtube.com/watch?v=$videoID&amp;feature=youtube_gdata'/>
		<link rel='http://$baseURL/schemas/1970#video.related' type='application/atom+xml' href='http://$baseURL/feeds/api/videos/$videoID/related?v=2'/>
		<link rel='http://$baseURL/schemas/1970#mobile' type='text/html' href='http://m.youtube.com/details?v=$videoID'/>
		<link rel='http://$baseURL/schemas/1970#uploader' type='application/atom+xml' href='http://$baseURL/feeds/api/users/$channelId?v=2'/>
		<link rel="http://$baseURL/schemas/2007#related" type="application/atom+xml" href="http://$baseURL/feeds/api/videos/$videoID/related" />
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
		<yt:hd/>
		<media:group>
			<media:category label='Howto &amp; Style' scheme='http://$baseURL/schemas/1970/categories.cat'>Howto</media:category>
			<media:content url='http://www.youtube.com/v/$videoID?version=3&amp;f=playlists&amp;app=youtube_gdata' type='application/x-shockwave-flash' medium='video' isDefault='true' expression='full' duration='0' yt:format='5'/>
		<media:content url='rtsp://r2---sn-a5m7zu7r.c.youtube.com/CiILENy73wIaGQmd8wY51H2D-BMYDSANFEgGUgZ2aWRlb3MM/0/0/0/video.3gp' type='video/3gpp' medium='video' expression='full' duration='31' yt:format='1'/>
		<media:content url='rtsp://r2---sn-a5m7zu7r.c.youtube.com/CiILENy73wIaGQmd8wY51H2D-BMYESARFEgGUgZ2aWRlb3MM/0/0/0/video.3gp' type='video/3gpp' medium='video' expression='full' duration='31' yt:format='6'/>
			<media:content url='http://$baseURL/$videoID.3gpp" type="video/3gpp' type='video/3gpp' medium='video' expression='full' duration='0' yt:format='1'/>
			<media:content url='http://$baseURL/$videoID.3gp' type='video/3gp' medium='video' expression='full' duration='0' yt:format='6'/>
			<media:content url='http://$baseURL/$videoID.mp4' type='video/mp4' medium='video' expression='full' duration='0' yt:format='18'/>
			<media:credit role='uploader' scheme='urn:youtube' yt:display='User'>$channelname</media:credit>
			<media:description type='plain'>$description</media:description>
			<media:keywords></media:keywords>
			<media:license type='standard' href='http://$baseURL/$videoID.license'/>
			<media:player url='http://www.youtube.com/watch?v=$videoID&amp;feature=youtube_gdata_player'/>
			<media:thumbnail url='$defaultTHURL' height='90' width='120' time='00:00:00'/>
			<media:thumbnail url='$mediumTHURL' height='180' width='320' time='00:00:00'/>
			<media:thumbnail url='$highTHURL' height='360' width='480' time='00:00:00'/>
			<media:title type='plain'>$videoname</media:title>
			<yt:aspectRatio>widescreen</yt:aspectRatio>
			<yt:duration seconds='$durationInSeconds'/>
			<yt:uploaded>$publishDate</yt:uploaded>
			<yt:uploaderId>$channelId</yt:uploaderId>
			<yt:videoid>$videoID</yt:videoid>
		</media:group>
		<gd:rating average='4.5' max='5' min='1' numRaters='100' rel='http://schemas.google.com/g/2005#overall'/>
		<yt:statistics favoriteCount='$favoriteCount' viewCount='$views'/>
		<yt:rating numDislikes='$dislikes' numLikes='$likes'/>
	</entry>
Entry;
$entries .= $entryiOS;
}
	// Generate the Atom feed
	echo <<<XML
<?xml version='1.0' encoding='UTF-8'?>
	$entries
XML;
}
}
function sanitizeResponse($jsonInput){
	return json_encode(json_decode($jsonInput));
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
