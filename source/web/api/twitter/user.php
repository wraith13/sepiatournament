<?php
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../twitteroauth.autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

$config = db_select_config($db);
	
function decode($config, $db, $json_list)
{
	$result = [];
	$now = time();
	$last_hour = $now -intval($config['twitter.user.cache.expire']);
	foreach($json_list as $i)
	{
		$at = new DateTime($i['at'], new DateTimeZone('UTC'));
		if ($last_hour < $at->getTimestamp())
		{
			$result[] = json_decode($i['json'], true);
		}
	}
	if (0 == count($result))
	{
		$twitter_user = get_twitter_user($config, $db);
		foreach($twitter_user as $i)
		{
			save_twitter_user_cache($db, $i);
			$result[] = $i;
		}
	}
	return $result;
}

function get_twitter_user($config, $db)
{
	$twitter = new TwitterOAuth
	(
		$config['twitter.consumer.key'],
		$config['twitter.consumer.secret'],
		$config['twitter.access.token'],
		$config['twitter.access.secret']
	);
	
	foreach(array('ids', 'screen_name') as $i)
	{
		if ($_REQUEST[$i])
		{
			return $twitter->get('users/lookup', [$i => $_REQUEST[$i],]);
		}
	}
	
	return null;
}

function get_twitter_user_cache($db)
{
	$condition = null;
	if ($_REQUEST['ids'])
	{
		$condition =
			'id in \'' .
			implode('\',\'',db_real_escape_array($db, explode(',', $_REQUEST['ids']))) .
			'\')';
	}
	else
	if ($_REQUEST['screen_name'])
	{
		$condition =
			'screen_name in \'' .
			implode('\',\'',db_real_escape_array($db, explode(',', $_REQUEST['screen_name']))) .
			'\')';
	}
	else
	{
		$condition = [];
		$condition['id'] = $user_id;
	}
	return db_select
	(
		$db,
		'twitter_user_cache',
		array('json', 'at'),
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
