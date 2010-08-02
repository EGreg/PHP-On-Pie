<?php

/**
 * PHP On Pie framework
 * by Gregory Magarshak
 * 
 * MIT (X11)  License
 * You are free to use this framework however you see fit.
 *
 * This file should be included by any PHP script that wants
 * to use the Pie framework. (For example, a front controller.)
 *
 * @package Pie
 */
 
// Enforce PHP version to be > 5.0
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
	die("PIE requires PHP version 5.0 or higher.");
}

// Was this loaded? In that case, do nothing.
if (defined('PIE_VERSION')) {
	return;
}
define('PIE_VERSION', 1.0);

//
// Constants
//

if (!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
if (!defined('PS'))
	define('PS', PATH_SEPARATOR);

if (!defined('PIE_DIR'))
	define('PIE_DIR', dirname(__FILE__));
if (!defined('PIE_CONFIG_DIR'))
	define('PIE_CONFIG_DIR', PIE_DIR.DS.'config');
if (!defined('PIE_CLASSES_DIR'))
	define('PIE_CLASSES_DIR', PIE_DIR.DS.'classes');
if (!defined('PIE_FILES_DIR'))
	define('PIE_FILES_DIR', PIE_DIR.DS.'files');
if (!defined('PIE_HANDLERS_DIR'))
	define('PIE_HANDLERS_DIR', PIE_DIR.DS.'handlers');
if (!defined('PIE_CONTROLLERS_DIR'))
	define('PIE_CONTROLLERS_DIR', PIE_DIR.DS.'controllers');
if (!defined('PIE_PLUGINS_DIR'))
	define('PIE_PLUGINS_DIR', PIE_DIR.DS.'plugins');
if (!defined('PIE_SCRIPTS_DIR'))
	define('PIE_SCRIPTS_DIR', PIE_DIR.DS.'scripts');
if (!defined('PIE_TESTS_DIR'))
	define('PIE_TESTS_DIR', PIE_DIR.DS.'tests');	
if (!defined('PIE_VIEWS_DIR'))
	define('PIE_VIEWS_DIR', PIE_DIR.DS.'views');

if (defined('APP_DIR')) {
	if (!defined('APP_CONFIG_DIR'))
		define('APP_CONFIG_DIR', APP_DIR.DS.'config');
	if (!defined('APP_CLASSES_DIR'))
		define('APP_CLASSES_DIR', APP_DIR.DS.'classes');
	if (!defined('APP_FILES_DIR'))
		define('APP_FILES_DIR', APP_DIR.DS.'files');
	if (!defined('APP_HANDLERS_DIR'))
		define('APP_HANDLERS_DIR', APP_DIR.DS.'handlers');
	if (!defined('APP_PLUGINS_DIR'))
		define('APP_PLUGINS_DIR', APP_DIR.DS.'plugins');
	if (!defined('APP_SCRIPTS_DIR'))
		define('APP_SCRIPTS_DIR', APP_DIR.DS.'scripts');
	if (!defined('APP_TESTS_DIR'))
		define('APP_TESTS_DIR', APP_DIR.DS.'tests');
	if (!defined('APP_VIEWS_DIR'))
		define('APP_VIEWS_DIR', APP_DIR.DS.'views');
	if (!defined('APP_WEB_DIR'))
		define('APP_WEB_DIR', APP_DIR.DS.'web');
}

//
// Include core classes
//
require(PIE_CLASSES_DIR.DS.'Pie.php'); 
require(PIE_CLASSES_DIR.DS.'Pie'.DS.'Bootstrap.php');
require(PIE_CLASSES_DIR.DS.'Pie'.DS.'Parameters.php');
require(PIE_CLASSES_DIR.DS.'Pie'.DS.'Config.php');
require(PIE_CLASSES_DIR.DS.'Pie'.DS.'Exception.php');
require(PIE_CLASSES_DIR.DS.'Pie'.DS.'Exception'.DS.'PhpError.php');
require(PIE_CLASSES_DIR.DS.'Db.php');
require(PIE_CLASSES_DIR.DS.'Db'.DS.'Query.php');
//
// Set things up
//

Pie::microtime();
Pie_Bootstrap::setIncludePath();
Pie_Bootstrap::registerAutoload();
Pie_Bootstrap::defineFunctions();
Pie_Bootstrap::registerExceptionHandler();
Pie_Bootstrap::registerErrorHandler();
Pie_Bootstrap::revertSlashes();
Pie_Bootstrap::configure();
Pie_Bootstrap::setDefaultTimezone();
Pie_Bootstrap::addAlias();
Pie_Request::baseUrl();

// NOTE: plugin config was loaded after app, but that shouldn't matter much

//
// Give the project a chance to load aggregated files, etc.
//
Pie::event('pie/init');
