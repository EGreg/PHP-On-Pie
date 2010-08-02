<?php

/**
 * The standard action front controller
 * @package Pie
 */
class Pie_ActionController
{
	static function execute()
	{
		// Fixes for different platforms:
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // ISAPI 3.0
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		
		// Define a constant
		if (!defined('PIE_CONTROLLER')) {
			define('PIE_CONTROLLER', 'Pie_ActionController');
		}
		
		try {
			$parts = explode('/', Pie_Request::tail());
			$parts_len = count($parts);
			if ($parts_len >= 1) {
				$module = $parts[0];
			}
			if ($parts_len >= 2) {
				$action = $parts[1];
			}
			
			// Make sure the 'pie'/'web' config fields are set,
			// otherwise URLs will be formed pointing to the wrong
			// controller script.
			$ar = Pie_Config::get('pie', 'web', 'appRootUrl', null);
			if (!isset($ar)) {
				throw new Pie_Exception_MissingConfig(array(
					'fieldpath' => 'pie/web/appRootUrl'
				));
			}
			$cs = Pie_Config::get('pie', 'web', 'controllerSuffix', null);
			if (!isset($cs)) {
				throw new Pie_Exception_MissingConfig(array(
					'fieldpath' => 'pie/web/controllerSuffix'
				));
			}
						
			// Dispatch the request
			$uri = Pie_Uri::from(compact('module', 'action'));
			Pie_Dispatcher::dispatch($uri);
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
