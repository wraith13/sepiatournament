<?php

ini_set( 'display_errors', 1); 

session_start();
$_SESSION = array();
$session_name = session_name();
if (isset($_COOKIE[$session_name]))
{
	setcookie($session_name, '', time() -86400, '/');
}
session_destroy();

header( 'location: /' );

?>
