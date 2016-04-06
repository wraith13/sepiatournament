<?php
require_once __DIR__ . '/common/db.php';

session_start();

function decode($json_list)
{
	$result = [];
	foreach($json_list as $i)
	{
		$current = json_decode($i["json"], true);
		$current["owner"] = $i["owner"];
		$current["is_private"] = $i["private"] ? true: false;
		$current["parent"] = $i["parent"];
		$result[] = $current;
	}
	return $result;
}

function get_object($db)
{
	$user_id = $_SESSION['user_id'];
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
	$condition["remove"] = 0;
	return db_select
	(
		$db,
		"object",
		array("parent", "owner", "private", "json"),
		$condition,
		"created_at desc"
	);
}

print
(
	json_encode
	(
		decode
		(
			get_object($db)
		)
	)
);

?>
