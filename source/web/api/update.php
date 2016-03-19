<?php
require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/common/user.php';
require_once __DIR__ . '/common/string.php';
require_once __DIR__ . '/uuid/uuid.php';

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
				"title" => typesafe_iconv_substr($link["title"], 0, 255)
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

function regulate_term($json, $label)
{
	$result = [];
	$result["name"] = $label;
	$term = $json[$label];
	if ($term)
	{
		$result["startAt"] = $term["startAt"];
		$result["endAt"] = $term["endAt"];
	}
	return $result;
}

function make_search($object)
{
	$result = $object["name"] . " " . $object["description"];
	if ($object["twitter"])
	{
		$result = $result . " " . $object["twitter"];
	}
	return $result;
}

function main($db)
{
	try
	{
		session_start();
		$user_id = $_SESSION['user_id'];
		
		if (!$user_id)
		{
			return array
			(
				"type" => "error",
				"message" => "not authenticated"
			);
		}
		
		$request_json = json_decode(file_get_contents('php://input'), true)["json"];
		$request_json_id = $request_json["id"];
		if (!$request_json_id)
		{
			$id = UUID::v4();
			$object = array
			(
				"id" => $id,
				"type" => $request_json["type"],
				"name" =>  typesafe_iconv_substr($request_json["name"], 0, 16),
				"description" =>  typesafe_iconv_substr($request_json["description"], 0, 1024),
				"links" => regulate_links($request_json["links"])
			);
			$is_private = $request_json["is_private"] ? 1: 0;
			switch($request_json["type"])
			{
			case "user":
				return array
				(
					"type" => "error",
					"message" => "id is null"
				);
				
			case "event":
				$object["term"] = regulate_term($request_json, "term");
				$object["entryTerm"] = regulate_term($request_json, "entryTerm");
				break;
				
			default:
				return array
				(
					"type" => "error",
					"message" => "unknown type"
				);
			}
			
			db_insert
			(
				$db,
				"object",
				array
				(
					"id" => $id,
					"type" => $request_json["type"],
					"owner" => $user_id,
					"private" => $is_private,
					"json" => json_encode($object),
					"search" => make_search($object)
				)
			);
			return array
			(
				"type" => "success",
				"json" => $object
			);
		}
		else
		{
			$object = db_select
			(
				$db,
				"object",
				array("json", "owner", "type", "remove"),
				array("id" => $request_json_id)
			)[0];
			
			if ($user_id != $object["owner"])
			{
				return array
				(
					"type" => "error",
					"message" => "disallow"
				);
			}
			
			$is_private = $request_json["is_private"];
			
			$object_json = json_decode
			(
				$object["json"],
				true
			);
			
			if ($request_json["type"] != $object["type"])
			{
				return array
				(
					"type" => "error",
					"message" => "type mismatch"
				);
			}
			
			$object_json = array_merge
			(
				$object_json,
				array
				(
					"name" => typesafe_iconv_substr($request_json["name"], 0, 16),
					"description" => typesafe_iconv_substr($request_json["description"], 0, 1024),
					"links" => regulate_links($request_json["links"])
				)
			);
			
			switch($request_json["type"])
			{
			case "user":
				$object_json["nnid"] = typesafe_iconv_substr($request_json["nnid"], 0, 16);
				break;
				
			case "event":
				$object_json["term"] = regulate_term($request_json, "term");
				$object_json["entryTerm"] = regulate_term($request_json, "entryTerm");
				break;
				
			default:
				return array
				(
					"type" => "error",
					"message" => "unknown type"
				);
			}
			
			db_update
			(
				$db,
				"object",
				array
				(
					"id" => $request_json_id,
					"private" => $is_private ? 1: 0,
					"json" => json_encode($object_json),
					"search" => make_search($object_json)
				),
				array("id")
			);
			return array
			(
				"type" => "success",
				"json" => $object_json
			);
		}
	}
	catch(Exception $e)
	{
		return array
		(
			"type" => "error",
			"message" => "error",
			"error" => $e->getMessage(),
		);
	}
}

print(json_encode(main($db)));

?>
