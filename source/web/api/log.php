<?php

require_once __DIR__ . '/common/db.php';

$result = array();

if (0 == count($error))
{
	$query_result = $db->query("select target, at, category, operator, message from log order by at desc");
	if ($query_result)
	{
		while($row = $query_result->fetch_assoc())
		{
			$result[] = array
			(
				target => $row["target"],
				at => $row["at"],
				category => $row["category"],
				operator => $row["operator"],
				message => $row["message"],
			);
		}
		$query_result->free();
	}
	else
	{
		$result = array
		(
			error => $db->error,
		);
	}
}
else
{
	$result = $error;
}

print(json_encode($result));

?>
