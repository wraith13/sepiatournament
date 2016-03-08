<?php

ini_set( 'display_errors', 1); 

require_once('twitteroauth.autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;
		
$result = array();
$config = parse_ini_file("../private/config.ini");
$dbconfig = parse_ini_file($config["dbconfig"]);
$db = new mysqli($dbconfig["dbserver"], $dbconfig["dbuser"], $dbconfig["dbpassword"], $dbconfig["dbname"]);
if ($db->connect_error)
{
	$result[] = array
	(
		errno => $db->connect_errno,
		error => $db->connect_error,
	);
}
else
{
	$db->set_charset($dbconfig["dbcharset"]);
	
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
		
		$callbackurl = preg_replace("oauth\.php", "oauth.\callback\.php", $_SERVER["PHP_SELF"]);
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
		$result[] = array
		(
			error => $db->error,
		);
	}
	
	$db->close();
}
print(json_encode($result));

?>
