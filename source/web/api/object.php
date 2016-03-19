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
	$request_type = $_REQUEST["type"];
	return db_select
	(
		$db,
		"object",
		array("parent", "owner", "private", "json"),
		$request_type ?
			array("type" => $request_type):
			array("id" => $_REQUEST["id"] || $user_id)
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
