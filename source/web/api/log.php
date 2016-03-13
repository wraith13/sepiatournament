<?php

require_once __DIR__ . '/common/db.php';

print
(
	json_encode
	(
		db_select
		(
			$db,
			"log",
			array("target", "at", "category", "operator", "message"),
			null,
			"at desc"
		)
	)
);


?>
