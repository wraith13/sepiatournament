<?php
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../twitteroauth.autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

$config = db_select_config($db);
	
function decode($config, $db, $json_list)
{
	$condition = [];
	if ($_REQUEST['ids'])
	{
		$condition['ids'] = explode(',', $_REQUEST['ids']);
	}
	else
	if ($_REQUEST['screen_name'])
	{
		$condition['screen_name'] = explode(',', $_REQUEST['screen_name']);
	}
	$result = [];
	$now = time();
	$last_hour = $now -intval($config['twitter.user.cache.expire']);
	foreach($json_list as $i)
	{
		$at = new DateTime($i['at'], new DateTimeZone('UTC'));
		if ($last_hour < $at->getTimestamp())
		{
			$result[] = json_decode($i['json'], true);
			if ($_REQUEST['ids'])
			{
				foreach ($condition['ids'] as $key => $value)
				{
					if ($i['id'] === $value)
					{
						unset($condition['ids'][$key]);
						break;
					}
				}
			}
			else
			if ($_REQUEST['screen_name'])
			{
				foreach ($condition['screen_name'] as $key => $value)
				{
					if (mb_strtolower($i['screen_name']) === mb_strtolower($value))
					{
						unset($condition['screen_name'][$key]);
						break;
					}
				}
			}
		}
	}
	if
	(	
		0 < count($condition['ids']) ||
		0 < count($condition['screen_name'])
	)
	{
		foreach ($condition as $key => $value)
		{
			$condition[$key] = implode(',', $value);
		}
		$twitter_user = get_twitter_user($config, $db, $condition);
		foreach($twitter_user as $i)
		{
			save_twitter_user_cache($db, $i);
			$result[] = $i;
		}
	}
	return $result;
}

function get_twitter_user($config, $db, $condition)
{
	$twitter = new TwitterOAuth
	(
		$config['twitter.consumer.key'],
		$config['twitter.consumer.secret'],
		$config['twitter.access.token'],
		$config['twitter.access.secret']
	);
	
	return $twitter->get('users/lookup', $condition);
}

function get_twitter_user_cache($db)
{
	$condition = null;
	if ($_REQUEST['ids'])
	{
		$condition =
			'id in (\'' .
			implode('\',\'',db_real_escape_array($db, explode(',', $_REQUEST['ids']))) .
			'\')';
	}
	else
	if ($_REQUEST['screen_name'])
	{
		$condition =
			'LOWER(screen_name) in (\'' .
			implode('\',\'',db_real_escape_array($db, explode(',',  mb_strtolower($_REQUEST['screen_name'])))) .
			'\')';
	}
	else
	{
		return [];
	}
	return db_select
	(
		$db,
		'twitter_user_cache',
		array('id', 'screen_name', 'json', 'at'),
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
