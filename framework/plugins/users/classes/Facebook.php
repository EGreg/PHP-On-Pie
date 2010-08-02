<?php

/**
 * This file gets included when someone wants to use the Facebook class
 */

set_include_path(get_include_path() . PS . dirname(__FILE__) . DS . 'Facebook');

if (Pie_Config::get('users', 'facebook', 'new', false)) {
	include('facebook_new.php');
} else {
	include('facebook.php');
}
