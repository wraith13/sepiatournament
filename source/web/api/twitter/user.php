<?php
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../twitteroauth.autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

$config = db_select_config($db);
	
function decode($config, $db, $json_list)
{
	$result = [];
	$now = time();
	$last_hour = $now -(60 *60);
	foreach($json_list as $i)
	{
		$at = new DateTime($i["at"], new DateTimeZone('UTC'));
		if ($last_hour < $at->getTimestamp())
		{
			$result[] = json_decode($i["json"], true);
		}
	}
	if (0 == count($result))
	{
		$twitter_user = get_twitter_user($config, $db);
		save_twitter_user_cache($db, $twitter_user);
		$result[] = $twitter_user;
	}
	return $result;
}

function get_twitter_user($config, $db)
{
	$twitter = new TwitterOAuth
	(
		$config["twitter.consumer.key"],
		$config["twitter.consumer.secret"],
		$config['twitter.access.token'],
		$config['twitter.access.secret']
	);
	
	foreach(array("id", "screen_name") as $i)
	{
		if ($_REQUEST[$i])
		{
			return $twitter->get("users/show", [$i => $_REQUEST[$i],]);
		}
	}
	
	return null;
}

function get_twitter_user_cache($db)
{
	$condition = [];
	foreach(array("id", "screen_name") as $i)
	{
		if ($_REQUEST[$i])
		{
			$condition[$i] = $_REQUEST[$i];
			break;
		}
	}
	if (0 == count($condition))
	{
		$condition["id"] = $user_id;
	}
	return db_select
	(
		$db,
		"twitter_user_cache",
		array("json", "at"),
		$condition
	);
}

print
(
	json_encode
	(
		decode
		(
			$config,
			$db,
			get_twitter_user_cache($db)
		)
	)
);

?>
