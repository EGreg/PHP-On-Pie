<?php

/**
 * Front controller for PIE
 */
include(dirname(__FILE__).DIRECTORY_SEPARATOR.'pie.inc.php');


//
// Handle the web request
//
Pie_ActionController::execute();
