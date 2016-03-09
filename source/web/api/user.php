<?php
require_once __DIR__ . '/common/db.php';

$result = array();

if (0 == count($error))
{
	$db->set_charset($dbconfig["dbcharset"]);
	
	session_start();
	$user_id = $_SESSION['user_id'];
	
	$query_result = $db->query("select json from object where id='$user_id';");
	if ($query_result)
	{
		while($row = $query_result->fetch_assoc())
		{
			$result[] = json_decode($row["json"]);
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
