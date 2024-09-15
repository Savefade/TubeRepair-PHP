<?php 
include "../../Config.php";
if(isset($_GET["channelId"])){
	if(isset($_SERVER['HTTP_X_TUBEREPAIR_API_KEY'])){ $APIkey = $_SERVER['HTTP_X_TUBEREPAIR_API_KEY'];}else{exit;}
$curlConnectionInitialization = curl_init("https://" . $APIurl . "/youtube/v3/channels?part=snippet&part=statistics&maxResults=" . $MaxCount . "&id=" . $_GET["channelId"] ."&key=" . $APIkey);
curl_setopt($curlConnectionInitialization, CURLOPT_HEADER, 0);
curl_setopt($curlConnectionInitialization, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curlConnectionInitialization);

$decodeResponce = json_decode($response, true);
$kindResponse = json_decode($response, false)->kind;
if($kindResponse == "youtube#channelListResponse"){
	//	echo $response;
	//$PlaylistID = $decodeResponce['items'][0]['id'];
	//$channelname = $decodeResponce['items'][0]['snippet']['channelTitle'];
	$description = htmlspecialchars($decodeResponce['items'][0]['snippet']['description'], ENT_QUOTES, 'UTF-8');
	$channelname = $decodeResponce['items'][0]['snippet']['title'];
	$publishDate = 	rtrim($decodeResponce['items'][0]['snippet']['publishedAt'], 'Z') . ".000Z"; //to be fixed
	$channelId = $decodeResponce['items'][0]['id'];
	$subscribers = 0;
	if(!$decodeResponce['items'][0]['statistics']['hiddenSubscriberCount']){
	$subscribers = $decodeResponce['items'][0]['statistics']['subscriberCount'];
	}
	$views = $decodeResponce['items'][0]['statistics']['viewCount'];
	//$etag = $decodeResponce['items'][$i]['etag'];
	$defaultTHURL = $decodeResponce['items'][0]['snippet']['thumbnails']['default']['url'];
	$mediumTHURL = $decodeResponce['items'][0]['snippet']['thumbnails']['medium']['url'];
	$highTHURL = $decodeResponce['items'][0]['snippet']['thumbnails']['high']['url'];
	$idproperty = md5($channelId);
	}
	//date issues
$youtubeXML = <<<XML
<entry
	xmlns='http://www.w3.org/2005/Atom'
	xmlns:media='http://search.yahoo.com/mrss/'
	xmlns:gd='http://schemas.google.com/g/2005'
	xmlns:yt='http://www.google.com/schemas/2007' gd:etag='W/&quot;$channelId.&quot;'>
	<id>tag:youtube.com,2008:channel:$channelId</id>
	<updated>1970-01-01T00:00:00.000Z</updated>
	<category scheme='http://schemas.google.com/g/2005#kind' term='http://www.google.com/schemas/2007#channel'/>
	<title>$channelname</title>
	<summary></summary>
	<link rel='alternate' type='text/html' href='https://www.youtube.com/channel/$channelId'/>
	<link rel='self' type='application/atom+xml' href='https://www.google.com/feeds/api/channels/$channelId?v=2'/>
	<author>
		<name>$channelname</name>
		<uri>https://$baseURL/feeds/api/users/$channelname</uri>
		<yt:userId>$channelId</yt:userId>
	</author>
	<yt:channelId>$channelId</yt:channelId>
	<yt:channelStatistics subscriberCount='$subscribers' viewCount='$views'/>
	<gd:feedLink rel='http://$baseURL/schemas/2007#channel.content' href='https://$baseURL/feeds/api/users/$channelId/uploads?v=2' countHint='144'/>
	<media:thumbnail url='$defaultTHURL'/>
</entry>
XML;
die($youtubeXML);
}
else{
	
die("An internal server error has occured! If there is a new version of the server please update to the latest version! Error: " . $response);
}
curl_close($curlConnectionInitialization);

function getDescription($videoId){
	$decodeResponce['items'][0]['snippet']['description'];
} 