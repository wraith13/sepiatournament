<?php
require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/common/string.php';
require_once __DIR__ . '/uuid/uuid.php';

function regulate_links($links)
{
	$result = [];
	foreach($links as $link)
	{
		if
		(
			'link' == $link['type'] &&
			(
				0 === strpos($link['url'], 'http:') ||
				0 === strpos($link['url'], 'https:') ||
				0 === strpos($link['url'], 'ftp:') ||
				0 === strpos($link['url'], 'tel:') ||
				0 === strpos($link['url'], 'mailto:')
			) &&
			iconv_strlen($link['url']) < 4096
		)
		{
			$result[] = array
			(
				'type' => $link['type'],
				'url' => $link['url'],
				'title' => typesafe_iconv_substr($link['title'], 0, 255)
			);
			if (10 <= count($result))
			{
				break;
			}
		}
		else
		{
			throw new Exception('invalid link data');
		}
	}
	return $result;
}

function regulate_term($json, $label)
{
	$result = [];
	$result['name'] = $label;
	$term = $json[$label];
	if ($term)
	{
		$result['startAt'] = $term['startAt'];
		$result['endAt'] = $term['endAt'];
	}
	return $result;
}

function regulate_id_array($id_array, $replace_id_array)
{
	$result = [];
	foreach($id_array as $id)
	{
		if
		(
			$id &&
			iconv_strlen($id) < 128
		)
		{
			if ($replace_id_array)
			{
				$result[] = $replace_id_array[$id] ?: $id;
			}
			else
			{
				$result[] = $id;
			}
			if (10 <= count($result))
			{
				break;
			}
		}
		else
		{
			throw new Exception('invalid id list data');
		}
	}
	return $result;
}

function regulate_users($db, $current_json, $request_json)
{
	$result = [];
	
	$request_users = $request_json['users'];
	$exist_users = $current_json['users'];
	
	$new_users = [];
	foreach ($request_users as $request_user)
	{
		if (!$request_user.id)
		{
			$hit = false;
			foreach ($exist_users as $user)
			{
				if ($request_user.screen_name == $user.screen_name)
				{
					$hit = true;
					break;
				}
			}
			if ($hit)
			{
				$new_users[] = array
				(
					'type' => 'twitter',
					'screen_name' = $request_user.screen_name,
					'tags' => 'invite',
				);
			}
		}
	}
	$remove_users = [];
	foreach ($exist_users as $user)
	{
		$hit = false;
		foreach ($request_users as $request_user)
		{
			if ($request_user.id == $user.id || $request_user.screen_name == $user.screen_name)
			{
				$hit = true;
				break;
			}
		}
		if (!$hit)
		{
			$remove_users[] = user;
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
		
		if (!$user_id)
		{
			return array
			(
				'type' => 'error',
				'message' => 'not authenticated'
			);
		}
		
		$request_data = json_decode(file_get_contents('php://input'), true);
		$request_json = $request_data['json'];
		
		if ($_SESSION['request_token'] != $request_data['request_token'])
		{
			return array
			(
				'type' => 'error',
				'message' => 'invalid request token'
			);
		}
		
		$request_buik = $request_data['bulk'];
		if ($request_buik)
		{
			$parent = $request_data['parent'];
			if ($parent && !db_has_write_permission($db, $user_id, $parent))
			{
				return array
				(
					'type' => 'error',
					'message' => 'disallow'
				);
			}
			
			$request_method = $request_data['method'];
			$request_type = $request_data['type'];
			if ('replace' == $request_method && $request_type)
			{
				db_update
				(
					$db,
					'object',
					array
					(
						'parent' => $parent,
						'type' => $request_type,
						'remove' => 1
					),
					array('parent', 'type')
				);
			}
			$index = 0;
			$replace_id_array = [];
			foreach($request_buik as $json)
			{
				$id = UUID::v4();
				$replace_id_array[$json['id']] = $id;
				$object = array
				(
					'id' => $id,
					'type' => $json['type'],
					'name' =>  typesafe_iconv_substr($json['name'], 0, 16),
					'description' =>  typesafe_iconv_substr($json['description'], 0, 1024),
					'links' => regulate_links($json['links']),
				);
				$is_private = $json['is_private'] ? 1: 0;
				switch($json['type'])
				{
					
				case 'match':
					$object['index'] = $index;
					$object['term'] = regulate_term($json, 'term');
					$object['entries'] = regulate_id_array($json['entries'], $replace_id_array);
					$object['level'] = intval(typesafe_iconv_substr($json['level'], 0, 16), 10);
					$object['weight'] = intval(typesafe_iconv_substr($json['weight'], 0, 16), 10);
					$object['winners'] = regulate_id_array($json['winners'], $replace_id_array);
					break;
					
				
				default:
					return array
					(
						'type' => 'error',
						'message' => 'unknown type'
					);
				}
				
				db_insert
				(
					$db,
					'object',
					array
					(
						'id' => $id,
						'type' => $json['type'],
						'parent' => $parent,
						'owner' => $user_id,
						'private' => $is_private,
						'json' => json_encode($object),
						'search' => make_search($object),
						'created_at' => 'dummy',
					)
				);
				
				++$index;
			}
			
			db_log_insert($db, $parent, $request_buik, $user_id, $request_type);
			return array
			(
				'type' => 'success',
				'json' => $object
			);
		}
		
		$request_json_id = $request_json['id'];
		if (!$request_json_id)
		{
			$parent = $request_json['parent'];
			if ($parent && !db_has_write_permission($db, $user_id, $parent))
			{
				return array
				(
					'type' => 'error',
					'message' => 'disallow'
				);
			}
			
			$id = UUID::v4();
			$object = array
			(
				'id' => $id,
				'type' => $request_json['type'],
				'name' =>  typesafe_iconv_substr($request_json['name'], 0, 16),
				'description' =>  typesafe_iconv_substr($request_json['description'], 0, 1024),
				'links' => regulate_links($request_json['links']),
			);
			$is_private = $request_json['is_private'] ? 1: 0;
			switch($request_json['type'])
			{
			case 'user':
				return array
				(
					'type' => 'error',
					'message' => 'id is null'
				);
				
			case 'event':
				$object['term'] = regulate_term($request_json, 'term');
				$object['entryTerm'] = regulate_term($request_json, 'entryTerm');
				$object['users'] = regulate_users($db, $object, $request_json);
				break;
				
			case 'entry':
				$object['users'] = regulate_users($db, $object, $request_json);
				break;
			
			default:
				return array
				(
					'type' => 'error',
					'message' => 'unknown type'
				);
			}
			
			db_insert
			(
				$db,
				'object',
				array
				(
					'id' => $id,
					'type' => $request_json['type'],
					'parent' => $parent,
					'owner' => $user_id,
					'private' => $is_private,
					'json' => json_encode($object),
					'search' => make_search($object),
					'created_at' => 'dummy',
				)
			);
			db_log_insert($db, $id, 'insert', $user_id, 'sucess');
			return array
			(
				'type' => 'success',
				'json' => $object
			);
		}
		else
		{
			if (!db_has_write_permission($db, $user_id, $request_json_id))
			{
				return array
				(
					'type' => 'error',
					'message' => 'disallow'
				);
			}
			
			$object = db_select
			(
				$db,
				'object',
				array('json', 'owner', 'type', 'remove'),
				array('id' => $request_json_id)
			)[0];
			
			$is_private = $request_json['is_private'];
			
			$object_json = json_decode
			(
				$object['json'],
				true
			);
			
			if ($request_json['type'] != $object['type'])
			{
				return array
				(
					'type' => 'error',
					'message' => 'type mismatch'
				);
			}
			
			$object_json = array_merge
			(
				$object_json,
				array
				(
					'name' => typesafe_iconv_substr($request_json['name'], 0, 16),
					'description' => typesafe_iconv_substr($request_json['description'], 0, 1024),
					'links' => regulate_links($request_json['links'])
				)
			);
			
			switch($request_json['type'])
			{
			case 'user':
				$object_json['nnid'] = typesafe_iconv_substr($request_json['nnid'], 0, 16);
				break;
				
			case 'event':
				$object_json['term'] = regulate_term($request_json, 'term');
				$object_json['entryTerm'] = regulate_term($request_json, 'entryTerm');
				$object_json['users'] = regulate_users($db, $object_json, $request_json);
				break;
				
			case 'match':
				$object_json['term'] = regulate_term($request_json, 'term');
				$object_json['entries'] = regulate_id_array($request_json['entries']);
				$object_json['level'] = intval(typesafe_iconv_substr($request_json['level'], 0, 16), 10);
				$object_json['weight'] = intval(typesafe_iconv_substr($request_json['weight'], 0, 16), 10);
				$object_json['winners'] = regulate_id_array($request_json['winners']);
				break;
				
			case 'entry':
				$object_json['users'] = regulate_users($db, $object_json, $request_json);
				break;
				
			default:
				return array
				(
					'type' => 'error',
					'message' => 'unknown type'
				);
			}
			
			db_update
			(
				$db,
				'object',
				array
				(
					'id' => $request_json_id,
					'private' => $is_private ? 1: 0,
					'json' => json_encode($object_json),
					'search' => make_search($object_json)
				),
				array('id')
			);
			db_log_insert($db, $request_json_id, 'update', $user_id, 'sucess');
			return array
			(
				'type' => 'success',
				'json' => $object_json
			);
		}
	}
	catch(Exception $e)
	{
		return array
		(
			'type' => 'error',
			'message' => 'error',
			'error' => $e->getMessage(),
		);
	}
}

print(json_encode(main($db)));
$_SESSION['request_token'] = UUID::v4();

?>
