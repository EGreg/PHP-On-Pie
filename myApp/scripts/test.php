<?php

/**
 * Front controller for Pie
 */

//
// Constants -- you might have to change these
//
define ('APP_DIR', realpath('..'));

//
// Include Pie
//
$header = "Testing...\n";
if (!is_dir(APP_DIR)) {
	die($header."Please edit test.php and change APP_DIR to point to your app's directory.");
}
$paths_filename = APP_DIR . '/local/paths.php';
$basename = basename(APP_DIR);
if (!file_exists($paths_filename)) {
	die($header."Please rename $basename/local.sample to $basename/local, and edit local/paths.php");
}
include($paths_filename);
include(realpath(PIE_DIR.'/pie.php'));

//
// Handle the web request
//
Pie_WebController::execute();
