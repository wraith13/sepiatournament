<?php
require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/common/string.php';
require_once __DIR__ . '/uuid/uuid.php';


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
		
		if ($_SESSION['request_token'] != $request_data['request_token'])
		{
			return array
			(
				'type' => 'error',
				'message' => 'invalid request token'
			);
		}
		
		$target = $request_json['target'];
		$item = $request_json['item'];
		if (!$target || !$item)
		{
			return array
			(
				'type' => 'error',
				'message' => 'invalid argument'
			);
		}
		
		if (!db_has_write_permission($db, $user_id, $target))
		{
			return array
			(
				'type' => 'error',
				'message' => 'disallow'
			);
		}
	
		db_insert_or_update
		(
			$db,
			'queue',
			array
			(
				'target' => $target,
				'type' => 'invite',
				'item' => $item,
				'at' => 'dummy',
			),
			array('targetid', 'type', 'item',)
		);
		db_log_insert($db, $target, 'insert', $user_id, "invite for $item");
		return array
		(
			'type' => 'success',
			'json' => $object
		);
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
