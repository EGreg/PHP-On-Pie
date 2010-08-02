<?php

/**
 * The standard web front controller
 * @package Pie
 */
class Pie_WebController
{
	static function execute()
	{
		// Fixes for different platforms:
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // ISAPI 3.0
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		
		// Define a constant
		if (!defined('PIE_CONTROLLER')) {
			define('PIE_CONTROLLER', 'Pie_WebController');
		}
		
		try {
			Pie::log("Request for " . Pie_Request::url(true));
			Pie_Dispatcher::dispatch();
			$dispatch_result = Pie_Dispatcher::result();
			if (!isset($dispatch_result)) {
				$dispatch_result = 'Ran dispatcher';
			}
			$uri = Pie_Dispatcher::uri();
			$module = $uri->module;
			$action = $uri->action;
			if ($module and $action) {
				$slot_names = Pie_Request::slotNames();
				$requested_slots = empty($slot_names) 
					? '' 
					: implode(',', array_keys($slot_names));
				Pie::log("~" . ceil(Pie::microtime()) . 'ms+'
					. ceil(memory_get_peak_usage()/1000) . 'kb.'
					. " $dispatch_result for $module/$action"
					. " ($requested_slots)"
				);
			} else {
				Pie::log("~" . ceil(Pie::microtime()) . 'ms+'
					. ceil(memory_get_peak_usage()/1000) . 'kb.'
					. " No route for " . $_SERVER['REQUEST_URI']);
			}
		} catch (Exception $exception) {
			Pie::event('pie/exception', compact('exception'));
		}
	}
}
