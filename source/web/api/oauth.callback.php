<?php

require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/uuid/uuid.php';
require_once __DIR__ . '/twitteroauth.autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

$result = array();
if (0 == count($error))
{
	$sns = $_GET["sns"];
	
	$twitter_config = select_config($db);
	
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
	try
	{
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
		$auth_id = $db->real_escape_string($twitter_user->id_str);
		$user_id = null;
		
		$auth_target = select_table_for_signle_column($db, "auth where type='twitter' and id='$auth_id';", "target");
		
		if ($auth_target && 0 < count($auth_target))
		{
			$target = $user_id = $auth_target[0];
			$auth_id = $db->real_escape_string($twitter_user->id_str);
			$auth_json = $db->real_escape_string(json_encode($twitter_user));
			$query_result = $db->query("update auth set json = '$auth_json' where type='twitter' and id='$auth_id';");
			if (!$query_result)
			{
				$result[] = array
				(
					error => $db->error,
				);
			}
			
			$query_result = $db->query("insert into log(target, at, category, operator, message) values('$user_id',UTC_TIMESTAMP(),'update','$user_id','login');");
			if (!$query_result)
			{
				$result[] = array
				(
					error => $db->error,
				);
			}
		}
		else
		{
			$user_id = UUID::v4();
			$user = array
			(
				type => "user",
				id => $user_id,
				name => $twitter_user->name,
				description => $twitter_user->description,
				links => [],
			);
			$user["links"][] = array
			(
				type => "link",
				title => "@" . $twitter_user->screen_name,
				url => "https://twitter.com/" . $twitter_user->screen_name,
			);
			if ($twitter_user->url && 0 < strlen($twitter_user->url))
			{
				$user["links"][] = array
				(
					type => "link",
					url => $twitter_user->url,
				);
			}
			$user_json = $db->real_escape_string(json_encode($user));
			$user_search = $db->real_escape_string($user["name"] . " " . $user["description"] . " " . $twitter_user->screen_name);
			$query_result = $db->query("insert into object(id, owner, type, json, search) values('$user_id', '$user_id', 'user', '$user_json','$user_search');");
			if (!$query_result)
			{
				$result[] = array
				(
					error => $db->error,
				);
			}
			$target = $user_id;
			
			$auth_id = $db->real_escape_string($twitter_user->id_str);
			$auth_json = $db->real_escape_string(json_encode($twitter_user));
			$query_result = $db->query("insert into auth(type, id, target, json) values('twitter','$auth_id','$user_id','$auth_json');");
			if (!$query_result)
			{
				$result[] = array
				(
					error => $db->error,
				);
			}
				
			$query_result = $db->query("insert into log(target, at, category, operator, message) values('$user_id',UTC_TIMESTAMP(),'insert','$user_id','login');");
			if (!$query_result)
			{
				$result[] = array
				(
					error => $db->error,
				);
			}
		}
		
		session_regenerate_id(true);
		$_SESSION['user_id'] = $user_id;
		
		header( 'location: /' );
	}
	catch(Exception $e)
	{
		$error_message = $db->real_escape_string($e->getMessage() . " @ " . $e->getTraceAsString());
		$query_result = $db->query("insert into log(target, at, category, operator, message) values('$target',UTC_TIMESTAMP(),'error','$target','$error_message');");
		if (!$query_result)
		{
			$result[] = array
			(
				error => $db->error,
			);
		}
		
		header( 'location: /' );
	}
}
else
{
	$result = $error;
}
print(json_encode($result));

?>
