<?php

require_once __DIR__ . '/abraham/twitteroauth/Token.php';
require_once __DIR__ . '/abraham/twitteroauth/TwitterOAuthException.php';
require_once __DIR__ . '/abraham/twitteroauth/Util.php';
require_once __DIR__ . '/abraham/twitteroauth/Request.php';
require_once __DIR__ . '/abraham/twitteroauth/Consumer.php';
require_once __DIR__ . '/abraham/twitteroauth/SignatureMethod.php';
require_once __DIR__ . '/abraham/twitteroauth/HmacSha1.php';
require_once __DIR__ . '/abraham/twitteroauth/Response.php';
require_once __DIR__ . '/abraham/twitteroauth/Config.php';
require_once __DIR__ . '/abraham/twitteroauth/Util/JsonDecoder.php';
require_once __DIR__ . '/abraham/twitteroauth/TwitterOAuth.php';

/**
 * Use to autoload needed classes without Composer.
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'Abraham\\TwitterOAuth\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/abraham\/twitteroauth/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
