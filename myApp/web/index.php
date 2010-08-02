<?php

/**
 * Front controller for Pie
 */

include(dirname(__FILE__).DIRECTORY_SEPARATOR.'pie.inc.php');

//
// Handle the web request
//
Pie_WebController::execute();
