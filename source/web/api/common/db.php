<?php
require_once __DIR__ . '/config.php';
$dbconfig = parse_ini_file($config["dbconfig"]);
$db = new mysqli($dbconfig["dbserver"], $dbconfig["dbuser"], $dbconfig["dbpassword"], $dbconfig["dbname"]);
if ($db->connect_error)
{
	throw new Exception
	(
		json_encode
		(
			array
			(
				errno => $db->connect_errno,
				error => $db->connect_error,
			)
		)
	);
}
else
{
	$db->set_charset($dbconfig["dbcharset"]);
}

function db_query($db, $query)
{
	$result = $db->query($query);
	if (!$result)
	{
		throw new Exception($db->error);
	}
	return $result;
}

function db_select_config($db)
{
	$result = [];
	$pre_result = db_select_table($db, "config", array("name", "value"));
	foreach($pre_result as $i)
	{
		$name = $i["name"];
		$value = $i["value"];
		$result[$name] = $value;
	}
	return $result;
}

function db_select_table($db, $from, $columns)
{
	$result = [];
	$columns_string = implode(",",$columns);
	$query_result = db_query($db, "select $columns_string from $from;");
	while($row = $query_result->fetch_assoc())
	{
		$current = [];
		foreach($columns as $i)
		{
			$current[$i] = $row[$i];
		}
		$result[] = $current;
	}
	$query_result->free();
	return $result;
}

function db_select_table_for_signle_column($db, $from, $column)
{
	$result = [];
	$query_result = db_query($db, "select $column from $from;");
	while($row = $query_result->fetch_assoc())
	{
		$result[] = $row[$column];
	}
	$query_result->free();
	return $result;
}

function db_log_insert($db, $a_target, $a_category, $a_operator, $a_message)
{
	$target = $db->real_escape_string($a_target);
	$category = $db->real_escape_string($a_category);
	$operator = $db->real_escape_string($a_operator);
	$message = $db->real_escape_string($a_message);
	return db_query($db, "insert into log(target, at, category, operator, message) values('$target',UTC_TIMESTAMP(),'$category','$operator','$message');");
}

function db_log_exception($db, $e, $target, $operator)
{
	return db_log_insert
	(
		$db,
		$target,
		"error",
		$target,
		$e->getMessage() . " @ " . $e->getTraceAsString()
	);
}
