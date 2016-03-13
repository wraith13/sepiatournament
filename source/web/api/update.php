<?php
require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/common/user.php';

function main($db)
{
	session_start();
	$user_id = $_SESSION['user_id'];
	
	$request_json = json_decode(file_get_contents('php://input'), true)["json"];
	$request_json_id = $request_json["id"];
	if (!$request_json_id)
	{
		return "id is null";
	}
	
	$object = db_select
	(
		$db,
		"object",
		array("json", "owner", "type", "remove"),
		array("id" => $request_json_id)
	)[0];
	
	if ($user_id != $object["owner"])
	{
		return "disallow";
	}
	
	$object_json = json_decode
	(
		$object["json"],
		true
	);
	
	if ($request_json["type"] != $object["type"])
	{
		return "type mismatch";
	}
	
	switch($request_json["type"])
	{
	case "user":
		$user = array_merge
		(
			$object_json,
			array
			(
				"name" => $request_json["name"],
				"description" => $request_json["description"],
				"nnid" => $request_json["nnid"]
			)
		);
		db_update
		(
			$db,
			"object",
			array
			(
				"id" => $request_json_id,
				"json" => json_encode($user),
				"search" => make_user_search($user)
			),
			array("id")
		);
		return "success";
	
	default:
		return "unknown type";
	
	}
}

print(main($db));

?>
