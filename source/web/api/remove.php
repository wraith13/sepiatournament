<?php
require_once __DIR__ . '/common/db.php';
require_once __DIR__ . '/uuid/uuid.php';

function main($db)
{
	try
	{
		session_start();
		$user_id = $_SESSION['user_id'];

		$request_data = json_decode(file_get_contents('php://input'), true);
		$target = $request_data['id'];
		
		if ($_SESSION['request_token'] != $request_data['request_token'])
		{
			return array
			(
				'type' => 'error',
				'message' => 'invalid request token'
			);
		}

		if (!db_has_write_permission($db, $user_id, $target))
		{
			return array
			(
				'type' => 'error',
				'message' => 'not authenticated'
			);
		}
		
		db_update
		(
			$db,
			'object',
			array
			(
				'id' => $target,
				'remove' => 1
			),
			array('id')
		);
		
		db_log_insert($db, $target, 'remove', $user_id, 'sucess');
		return array
		(
			'type' => 'success',
			'json' => null
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
