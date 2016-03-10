<?php

require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/twitteroauth.autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

session_start();

$sns = $_GET["sns"];

$twitter_config = db_select_config($db);
	
$twitter = new TwitterOAuth
(
	$twitter_config["twitter.consumer.key"],
	$twitter_config["twitter.consumer.secret"]
);

$callbackurl = preg_replace("oauth\.php", "oauth\.callback\.php", $_SERVER["PHP_SELF"]);
//$callbackurl = "http:/sepiatournament.net/api/oauth.callback.php?sns=$sns";
$request_token = $twitter->oauth
(
	'oauth/request_token',
	array('oauth_callback' => $callbackurl)
);
$url = $twitter->url
(
	'oauth/authenticate',
	array('oauth_token' => $request_token['oauth_token'])
);
$_SESSION['oauth_sns'] = $sns;
$_SESSION['oauth_token'] = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

header( 'location: '. $url );

?>
