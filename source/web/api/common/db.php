<?php
require_once __DIR__ . '/config.php';
$dbconfig = parse_ini_file($config['dbconfig']);
$db = new mysqli($dbconfig['dbserver'], $dbconfig['dbuser'], $dbconfig['dbpassword'], $dbconfig['dbname']);
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
	$db->set_charset($dbconfig['dbcharset']);
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
	$pre_result = db_select($db, 'config', array('name', 'value'));
	foreach($pre_result as $i)
	{
		$name = $i['name'];
		$value = $i['value'];
		$result[$name] = $value;
	}
	return $result;
}

function db_select($db, $table, $columns, $wheres = null, $orderby = null)
{
	$result = [];
	$columns_string = implode(',', $columns);
	$from = $table;
	if ($wheres)
	{
		$where_string_array = [];
		foreach(db_real_escape_array($db, $wheres) as $key => $value)
		{
			$where_string_array[] = "$key='$value'";
		}
		$where_string = implode(' and ', $where_string_array);
		$from = "$from where $where_string";
	}
	if ($orderby)
	{
		$from = "$from order by $orderby";
	}
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
		'error',
		$target,
		$e->getMessage() . ' @ ' . $e->getTraceAsString()
	);
}

function db_real_escape_array($db, $array)
{
	$result = [];
	foreach($array as $key => $value)
	{
		$result[$key] = $db->real_escape_string($value);
	}
	return $result;
}
function db_insert($db, $table, $array)
{
	$keys = [];
	$values = [];
	foreach(db_real_escape_array($db, $array) as $key => $value)
	{
		$keys[] = $key;
		if ('created_at' == $key || 'at' == $key)
		{
			$values[] = 'UTC_TIMESTAMP()';
		}
		else
		{
			$values[] = "'" . $value . "'";
		}
	}
	$kes_string = implode(',' ,$keys);
	$values_string = implode(",", $values);
	return db_query($db, "insert into $table($kes_string) values($values_string);");
}
function db_update($db, $table, $array, $primary_keys)
{
	$sets = [];
	$wheres = [];
	foreach(db_real_escape_array($db, $array) as $key => $value)
	{
		$part = "$key='$value'";
		if (in_array($key, $primary_keys))
		{
			$wheres[] = $part;
		}
		else
		{
			$sets[] = $part;
		}
	}
	
	$set_string = implode("," ,$sets);
	$where_string = implode(" and ", $wheres);
	
	return db_query($db, "update $table set $set_string where $where_string;");
}
function db_insert_or_update($db, $table, $array, $primary_keys)
{
	$keys = [];
	$values = [];
	foreach(db_real_escape_array($db, $array) as $key => $value)
	{
		$keys[] = $key;
		if ('created_at' == $key || 'at' == $key)
		{
			$escaped_value = 'UTC_TIMESTAMP()';
		}
		else
		{
			$escaped_value = "'" . $value . "'";
		}
		$values[] = $escaped_value;
		if (!in_array($key, $primary_keys))
		{
			$sets[] = "$key=$escaped_value";
		}
	}
	$kes_string = implode(',' ,$keys);
	$values_string = implode(',', $values);
	$set_string = implode("," ,$sets);
	return db_query($db, "insert into $table($kes_string) values($values_string) on duplicate key update $set_string;");
}
function db_has_write_permission($db, $user_id, $target_id)
{
	if ($user_id && $target_id)
	{
		if ($user_id == $target_id)
		{
			return true;
		}
		
		$array = db_select
		(
			$db,
			'object',
			array('id', 'parent', 'owner', 'json'),
			array('id' => $target_id)
		)[0];
		
		if ($user_id == $array['owner'])
		{
			return true;
		}
		
		//if (json 内の users 内に $user_id と一致するユーザーが入れば)
		//{
		//	return true;
		//}
		
		return db_has_write_permission($db, $user_id, $array['parent']);
	}
	return false;
}

function make_search($object)
{
	$result = $object['name'] . ' ' . $object['description'];
	if ($object['twitter'])
	{
		$result = $result . ' ' . $object['twitter'];
	}
	return $result;
}

function save_twitter_user_cache($db, $twitter_user)
{
	db_insert_or_update
	(
		$db,
		'twitter_user_cache',
		array
		(
			'id' => $twitter_user->id_str,
			'screen_name' => $twitter_user->screen_name,
			'at' => 'dummy',
			'json' => json_encode($twitter_user),
		),
		array('id')
	);
}
