<?php
require_once __DIR__ . '/common/db.php';

function main($db)
{
	session_start();
	$user_id = $_SESSION['user_id'];
	
	$json = json_decode(file_get_contents('php://input'), true)["json"];
	$json_id = $json["id"];
	if (!$json_id)
	{
		return "id is null";
	}
	
	$object = db_select_table
	(
		$db,
		"object where id='$json_id'",
		array("json", "owner", "type", "remove")
	)[0];
	
	if ($user_id != $object["owner"])
	{
		return "disallow($user_id,{$object['owner']}";
	}
	
	switch($json["type"])
	{
	case "user":
		$user = json_decode
		(
			$object["json"],
			true
		);
		$user["name"] = $json["name"];
		$user["description"] = $json["description"];
		$user["nnid"] = $json["nnid"];
		$user_json = $db->real_escape_string(json_encode($user));
		$user_search = $db->real_escape_string($user["name"] . " " . $user["description"] . " " . $user["twitter"]);
		$query_result = db_query($db, "update object set json='$user_json', search='$user_search' where id='$json_id';");
		return "success";
	
	default:
		return "unknown type";
	
	}
}

print(main($db));

?>
