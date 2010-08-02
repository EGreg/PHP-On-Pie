<?php

/**
 * Front controller for Pie
 */

//
// Constants -- you might have to change these
//
define ('APP_DIR', dirname(dirname(__FILE__)));

//
// Include Pie
//
$header = "This is a PHP On Pie project...\n";
if (!is_dir(APP_DIR)) {
	die($header."Please edit scripts/pie.inc.php and change APP_DIR 
to point to your app's directory.");
}
$paths_filename = APP_DIR . '/local/paths.php';
$basename = basename(APP_DIR);
if (!file_exists($paths_filename)) {
	die($header."Please rename $basename/local.sample to $basename/local, and edit local/paths.php\n");
}
include($paths_filename);
include(realpath(PIE_DIR.'/pie.php'));
