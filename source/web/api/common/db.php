<?php
require_once __DIR__ . '/config.php';
if (0 == count($error))
{
	$dbconfig = parse_ini_file($config["dbconfig"]);
	$db = new mysqli($dbconfig["dbserver"], $dbconfig["dbuser"], $dbconfig["dbpassword"], $dbconfig["dbname"]);
	if ($db->connect_error)
	{
		$error[] = array
		(
			errno => $db->connect_errno,
			error => $db->connect_error,
		);
	}
	else
	{
		$db->set_charset($dbconfig["dbcharset"]);
	}
}
