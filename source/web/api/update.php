<?php
require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/common/user.php';

function regulate_links($links)
{
	$result = [];
	foreach($links as $link)
	{
		if
		(
			"link" == $link["type"] &&
			(
				0 === strpos($link["url"], "http:") ||
				0 === strpos($link["url"], "https:") ||
				0 === strpos($link["url"], "ftp:") ||
				0 === strpos($link["url"], "tel:") ||
				0 === strpos($link["url"], "mailto:")
			) &&
			iconv_strlen($link["url"]) < 4096
		)
		{
			$result[] = array
			(
				"type" => $link["type"],
				"url" => $link["url"],
				"title" => iconv_substr($link["title"], 0, 255)
			);
			if (10 <= count($result))
			{
				break;
			}
		}
		else
		{
			throw new Exception("invalid link data");
		}
	}
	return $result;
}

function main($db)
{
	try
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
					"name" => iconv_substr($request_json["name"], 0, 16),
					"description" => iconv_substr($request_json["description"], 0, 1024),
					"nnid" => iconv_substr($request_json["nnid"], 0, 16),
					"links" => regulate_links($request_json["links"])
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
	catch(Exception $e)
	{
		return $e->getMessage();
	}
}

print(main($db));

?>
