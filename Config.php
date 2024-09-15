<?php
//Maintenance
$underMaintenance = false; // put the server in maintenance
//config
$skipHandshakeLogin = false; // skip the handshake when opening the app
$invidiousURL = "inv.tux.pizza"; // invidious.fdn.fr TubeRepair uses an invidious endpoint to get youtube videos. if the default doesnt work please find one here: https://docs.invidious.io/instances/
$APIkey = ""; // insert your api key here!
$baseURL = "172.20.10.2"; // enter your url where this is hosted NOTE: without any / with http/s. If you are hosting this in a directory (not /) add a / then your folder
$APIurl = "www.googleapis.com"; //incase they change the api to a new url you can input it here
$customAPIKeyHeader = "HTTP_X_TUBEREPAIR_API_KEY"; //here you have to put the custom API header that the users will input in settings. NOTE: for advanced users only!
$MaxCount = 50; // the amount of results the api will search for (max 50)
$maxCommentCountResult = 50; // the amount of comments the api will retrieve (max 50)
$forceSupport = false;

function sanitizeResponse($json){
	return json_encode(json_decode($json));
}