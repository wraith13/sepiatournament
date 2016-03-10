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

function select_table($db, $from, $columns)
{
	$result = [];
	$columns_string = implode(",",$columns);
	$query_result = $db->query("select $columns_string from $from;");
	if ($query_result)
	{
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
	}
	else
	{
		$result = null;
	}
	
	return $result;
}

function select_table_for_signle_column($db, $from, $column)
{
	$result = [];
	$query_result = $db->query("select $column from $from;");
	if ($query_result)
	{
		while($row = $query_result->fetch_assoc())
		{
			$result[] = $row[$column];
		}
		$query_result->free();
	}
	else
	{
		$result = null;
	}
	
	return $result;
}

function select_config($db)
{
	$result = null;
	$pre_result = select_table($db, "config", array("name", "value"));
	if ($pre_result)
	{
		$result = [];
		foreach($pre_result as $i)
		{
			$name = $i["name"];
			$value = $i["value"];
			$result[$name] = $value;
		}
	}
	return $result;
}
