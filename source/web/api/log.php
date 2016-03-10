<?php

require_once __DIR__ . '/common/db.php';

print
(
	json_encode
	(
		db_select_table
		(
			$db,
			"log order by at desc",
			array("target", "at", "category", "operator", "message")
		)
	)
);


?>
