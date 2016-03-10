<?php

require_once __DIR__ . '/common/db.php';

print
(
	json_encode
	(
		0 == count($error) ?
		(
			select_table
			(
				$db,
				"log order by at desc",
				array("target", "at", "category", "operator", "message")
			) ?:
			array(error => $db->error)
		):
		$error
	)
);


?>
