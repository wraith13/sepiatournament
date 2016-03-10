<?php
require_once __DIR__ . '/common/db.php';

session_start();
$user_id = $_SESSION['user_id'];

function decode($json_list)
{
	$result = [];
	foreach($json_list as $i)
	{
		$result[] = json_decode($i);
	}
	return $result;
}

print
(
	json_encode
	(
		decode
		(
			db_select_table_for_signle_column
			(
				$db,
				"object where id='$user_id'",
				"json"
			)
		)
	)
);


?>
