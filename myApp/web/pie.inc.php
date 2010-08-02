<?php

//
// Constants -- you might have to change these
//
if (!defined('APP_DIR')) {
	define ('APP_DIR', dirname(dirname(__FILE__)));
}

//
// Include Pie
//
$header = "<h1>This is a PHP On Pie project...</h1>\n";
if (!is_dir(APP_DIR)) {
	die($header."Please edit index.php and change APP_DIR to point to your app's directory.");
}

$paths_filename = realpath(APP_DIR . '/local/paths.php');
if (!file_exists($paths_filename)) {
	$basename = basename(APP_DIR);
	die($header."Please rename $basename/local.sample to $basename/local, and edit local/paths.php");
}

include($paths_filename);
$pie_filename = realpath(PIE_DIR.'/pie.php');
if (!file_exists($pie_filename)) {
	$basename = basename(APP_DIR);
	die($header."Please have the correct path to PIE in $basename/local/paths.php");
}

include($pie_filename);
