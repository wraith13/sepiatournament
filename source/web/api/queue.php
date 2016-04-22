<?php
require_once __DIR__ . '/common/db.php';

session_start();

function get_queue($db)
{
	$user_id = $_SESSION['user_id'];
	$condition = [];
	foreach(array('owner', 'target', 'type') as $i)
	{
		if ($_REQUEST[$i])
		{
			if ('owner' == $i)
			{
				//	いまの table だけじゃ実現できないので一旦保留。。。
				break;
			}
			$condition[$i] = $_REQUEST[$i];
		}
	}
	if (0 == count($condition))
	{
		$condition['id'] = $user_id;
	}
	$condition['remove'] = 0;
	return db_select
	(
		$db,
		'queue',
		array('target', 'type', 'item', 'at'),
		$condition,
		'at desc'
	);
}

print
(
	json_encode
	(
		get_queue($db)
	)
);

?>
