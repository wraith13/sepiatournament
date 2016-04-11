<?php
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../twitteroauth.autoload.php';

session_start();

function decode($json_list)
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
	return $result;
}

function get_twitter_user($db)
{
	$twitter_config = db_select_config($db);
	$twitter = new TwitterOAuth
	(
		$twitter_config["twitter.consumer.key"],
		$twitter_config["twitter.consumer.secret"],
		$twitter_config['twitter.access.token'],
		$twitter_config['twitter.access.secret']
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
			get_twitter_user_cache($db)
		)
	)
);

?>
