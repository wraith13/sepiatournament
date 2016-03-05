<?php

ini_set( 'display_errors', 1); 

$result = array();
$config = parse_ini_file("../private/config.ini");
$dbconfig = parse_ini_file($config["dbconfig"]);
$db = new mysqli($dbconfig["dbserver"], $dbconfig["dbuser"], $dbconfig["dbpassword"], $dbconfig["dbname"]);
if ($db->connect_error)
{
		$result[] = array
		(
			errno => $db->connect_errno,
			error => $db->connect_error,
		);
}
else
{
	$db->set_charset($dbconfig["dbcharset"]);
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
		$result[] = array
		(
			error => $db->error,
		);
	}
	$db->close();
}
print(json_encode($result));

?>
