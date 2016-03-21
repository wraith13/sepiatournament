<?php
require_once __DIR__ . '/uuid/uuid.php';

session_start();
$_SESSION['request_token'] = UUID::v4();
print($_SESSION['request_token']);

?>
