<?php

require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/twitteroauth.autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

if (0 == count($error))
{
	$sns = $_GET["sns"];
	
	$query_result = $db->query("select name, value from config where name like 'twitter%'");
	if ($query_result)
	{
		$twitter_config = array();
		while($row = $query_result->fetch_assoc())
		{
			$twitter_config[$row["name"]] = $row["value"];
		}
		$query_result->free();
		
		session_start();
		
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
	}
	else
	{
		$error[] = array
		(
			error => $db->error,
		);
	}
}
print(json_encode($error));

?>
