<?php

require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/uuid/uuid.php';
require_once __DIR__ . '/twitteroauth.autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

function get_twitter_image_url($twitter_user)
{
	return preg_replace('/_normal\.([^\.]*)$/', '.\1', $twitter_user->profile_image_url_https);
}

try
{
	$sns = $_GET["sns"];
	
	$twitter_config = db_select_config($db);
	
	session_start();
	
	$request_token = [];
	$request_token['oauth_token'] = $_SESSION['oauth_token'];
	$request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];
	
	if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token'])
	{
		die( 'Error!' );
	}
	
	$twitter = new TwitterOAuth
	(
		$twitter_config["twitter.consumer.key"],
		$twitter_config["twitter.consumer.secret"],
		$request_token['oauth_token'],
		$request_token['oauth_token_secret']
	);
	
	$target = "unknown";
	
	//	通常であれば投稿や情報取得の為にこの access_token を保存するところだが、このシステムではユーザー情報を取得した後は利用しない為、保存しない。
	$access_token = $twitter->oauth
	(
		"oauth/access_token",
		array("oauth_verifier" => $_REQUEST['oauth_verifier'])
	);
	
	$twitter = new TwitterOAuth
	(
		$twitter_config["twitter.consumer.key"],
		$twitter_config["twitter.consumer.secret"],
		$access_token['oauth_token'],
		$access_token['oauth_token_secret']
	);
	$twitter_user = $twitter->get("account/verify_credentials");
	save_twitter_user_cache($db, $twitter_user);
	$auth_id = $db->real_escape_string($twitter_user->id_str);
	$user_id = null;
	
	$auth_target = db_select
	(
		$db,
		"auth",
		array("target"),
		array
		(
			"type" => "twitter",
			"id" => $auth_id
		)
	);
	
	if (0 < count($auth_target))
	{
		$target = $user_id = $auth_target[0]["target"];
		
		//	update auth
		db_update
		(
			$db,
			"auth",
			array
			(
				"type" => "twitter",
				"id" => $auth_id,
				"json" => json_encode($twitter_user)
			),
			array("type", "id")
		);
		
		//	update object(user)
		$user = array_merge
		(
			json_decode
			(
				db_select
				(
					$db,
					"object",
					array("json"),
					array("id"=>$user_id)
				)
				[0]["json"],
				true
			),
			array
			(
				"twitter" => $twitter_user->screen_name,
				"image" => get_twitter_image_url($twitter_user)
			)
		);
		db_update
		(
			$db,
			"object",
			array
			(
				"id" => $user_id,
				"json" => json_encode($user),
				"search" => make_search($user)
			),
			array("id")
		);
		
		//	log
		db_log_insert($db, $user_id, "update", $user_id, "login");
	}
	else
	{
		//	insert object(user)
		$user_id = UUID::v4();
		$user = array
		(
			"type" => "user",
			"id" => $user_id,
			"name" => $twitter_user->name,
			"description" => $twitter_user->description,
			"twitter" => $twitter_user->screen_name,
			"image" => get_twitter_image_url($twitter_user),
			"links" => [],
		);
		if ($twitter_user->url)
		{
			$url = $twitter_user->url;
			if
			(
				$twitter_user->entities &&
				$twitter_user->entities->url &&
				$twitter_user->entities->url->urls &&
				$twitter_user->entities->url->urls[0] &&
				$twitter_user->entities->url->urls[0]->expanded_url
			)
			{
				$url = $twitter_user->entities->url->urls[0]->expanded_url;
			}
			
			$user["links"][] = array
			(
				"type" => "link",
				"url" => $url
			);
		}
		db_insert
		(
			$db,
			"object",
			array
			(
				"id" => $user_id,
				"owner" => $user_id,
				"type" => "user",
				"json" => json_encode($user),
				"search" => make_search($user),
				"created_at" => "dummy",
			)
		);
		$target = $user_id;
		
		//	insert auth
		db_insert
		(
			$db,
			"auth",
			array
			(
				"type" => "twitter",
				"id" => $auth_id,
				"target" => $user_id,
				"json" => json_encode($twitter_user)
			)
		);
		
		//	log
		db_log_insert($db, $user_id, "insert", $user_id, "login");
	}
	
	session_regenerate_id(true);
	$_SESSION['user_id'] = $user_id;
	$_SESSION['request_token'] = UUID::v4();
}
catch(Exception $e)
{
	db_log_exception($db, $e, $target, $target);
}

header( 'location: /' );

?>
