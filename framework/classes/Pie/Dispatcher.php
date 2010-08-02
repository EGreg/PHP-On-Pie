<?php

/**
 * This class lets you dispatch requests
 * @package Pie
 */
class Pie_Dispatcher
{
	/**
	 * Returns the URI that is currently being dispatched.
	 * You should usually use this instead of Pie_Request::uri(),
	 * as they may be different after a call to Pie_Request::forward()
	 * @return Pie_Uri
	 */
	static function uri()
	{
		if (isset(self::$uri)) {
			return self::$uri;
		}
		return Pie_Request::uri();
	}

	/**
	 * Forwards internally to a new URL, starting the dispatcher loop again
 	 * @param mixed $uri
	 *  The URI to forward to, either as a string, an array or a Pie_Uri object.
	 * @param mixed $check
	 *  Optional. Pass array() here to skip checking whether the URI can be obtained
	 *  as a result of routing some URL.
	 * @param array $skip
	 *  Optional. Pass an array of events to avoid firing the next time through the
	 *  dispatcher loop.
	 * @throws Pie_Exception_DispatcherForward
	 * @throws Pie_Exception_WrongType
	 */
	static function forward(
		$uri, 
		$check = array('accessible'),
		$skip = null)
	{
		if (!is_array($check)) {
			$check = array('accessible');
		}
		if (in_array('accessible', $check)) {
			if (! Pie_Uri::url($uri)) {
				throw new Pie_Exception_WrongType(array(
					'field' => '$uri',
					'range' => 'accessible destination'
				));
			}
		}
		
		// Throw an exception that only the dispatcher should catch.
		throw new Pie_Exception_DispatcherForward(
			compact('uri', 'skip')
		);
	}
	
	/**
	 * Stops processing the request and asks the dispatcher
	 * to jump straight to displaying the errors.
	 * @throws Pie_Exception_DispatcherErrors
	 */
	static function showErrors()
	{
		// Throw an exception that only the dispatcher should catch.
		throw new Pie_Exception_DispatcherErrors();
	}

	/**
	 * Used to get/set the result of the dispatching
	 * @param string $new_result
	 *  Optional. Pass a string here to record a result of the dispatching.
	 * @param bool $overwrite
	 *  Defaults to false. If a result is already set, doesn't override it
	 *  unless you pass true here.
	 */
	static function result($new_result = null, $overwrite = false)
	{
		static $result = null;
		if (isset($new_result)) {
			if (!isset($result) or $overwrite === true) {
				$result = $new_result;
			}
		}
		return $result;
	}
	
	/**
	 * Dispatches a URI for internal processing.
	 * Usually called by a front controller.
	 * @param mixed $uri
	 *  Optional. You can pass a custom URI to dispatch. Otherwise, PIE will attempt
	 *  to route the requested URL, if any.
	 * @param array $check
	 *  Optional. Pass array() to skip checking whether the URI can be obtained
	 *  as a result of routing some URL.
	 * @return boolean
	 */
	static function dispatch(
		$uri = null, 
		$check = array('accessible'))
	{
		if (!is_array($check)) {
			$check = array('accessible');
		}

		if (isset($uri)) {
			if (in_array('accessible', $check)) {
				if (! Pie_Uri::url($uri)) {
					// We shouldn't dispatch to this URI
					$uri = Pie_Uri::from(array());
				}
			}
			self::$uri = Pie_Uri::from($uri);
		} else {
			self::$uri = Pie_Request::uri();
		}

		// if file or dir is requested, try to serve it
		$served = false;
		$skip = Pie_Config::get('pie', 'dispatcherSkipFilename', false);
		$filename = $skip ? false : Pie_Request::filename();
		if ($filename) {
			if (is_dir($filename)) {
				$served = Pie::event("pie/dir", compact('filename', 'routed_uri'));
				$dir_was_served = true;
			} else {
				$served = Pie::event("pie/file", compact('filename', 'routed_uri'));
				$dir_was_served = false;
			}
		}

		// if response was served, then return
		if ($served) {
			self::result($dir_was_served ? "Dir served" : "File served");
			return true;
		}

		// This loop is for forwarding
		$max_forwards = Pie_Config::get('pie', 'maxForwards', 10);
		for ($try = 0; $try < $max_forwards; ++$try) {

			// Make an array from the routed URI
			$routed_uri_array = array();
			if (self::$uri instanceof Pie_Uri) {
				$routed_uri_array = self::$uri->toArray();
			}

			// If no module was found, then respond with noModule and return
			if (!isset(self::$uri->module)) {
				Pie::event("pie/noModule", $routed_uri_array); // should echo things
				self::result("No module");
				return false;
			}
			
			$module = self::$uri->module;

			// Implement restricting of modules we are allowed to access
			$routed_modules = Pie_Config::get('pie', 'routedModules', null);
			if (isset($routed_modules)) {
				if (!in_array($module, $routed_modules)) {
					Pie::event('pie/notFound', $routed_uri_array); // should echo things
					self::result("Unknown module");
					return false;
				}
			} else {
				if (!Pie::realPath("handlers/$module")) {
					Pie::event('pie/notFound', $routed_uri_array); // should echo things
					self::result("Unknown module");
					return false;
				}
			}
			
			try {
				// Fire a pure event, for aggregation etc
				if (!in_array('pie/prepare', self::$skip)) {
					Pie::event('pie/prepare', $routed_uri_array, true);
				}
	
				// Perform validation
				if (!in_array('pie/validate', self::$skip)) {
					Pie::event('pie/validate', $routed_uri_array);
				
					// Check if any errors accumulated
					if (Pie_Response::getErrors()) {
						// There were validation errors -- render a response
						self::errors(null, $module, null);
						self::result('Validation errors');
						return false;
					}
				}
				
				// Time to instantiate some app objects from the request
				if (!in_array('pie/objects', self::$skip)) {
					Pie::event('pie/objects', $routed_uri_array, true);
				}
				
				// We might want to reroute the request
				if (!in_array('pie/reroute', self::$skip)) {
					$stop_dispatch = Pie::event('pie/reroute', $routed_uri_array, true);
					if ($stop_dispatch) {
						self::result("Stopped dispatch");
						return false;
					}
				}
				if (Pie_Request::isPost()) {
					if (!in_array('pie/post', self::$skip)) {
						// Make some changes to server state, possibly
						Pie::event('pie/post', $routed_uri_array);
					}
				}

				// Time to instantiate some app objects from the request
				if (!in_array('pie/analytics', self::$skip)) {
					Pie::event('pie/analytics', $routed_uri_array, true);
				}
				
				// Start buffering the response, unless otherwise requested
				if ($handler = Pie_Response::isBuffered()) {
					$ob = new Pie_OutputBuffer($handler);
				}

				// Generate and render a response
				Pie::event("pie/response", $routed_uri_array);
				if (!empty($ob)) {
					$ob->endFlush();
				}
				self::result("Served response");
				return true;
			} catch (Pie_Exception_DispatcherForward $e) {
				// Go again, this time with a different URI.
				self::$uri = Pie_Uri::from($e->uri);
				if (is_array($e->skip)) {
					self::$skip = $e->skip;
				} else {
					// Don't process the POST fields this time around
					self::$skip = array('pie/post');
				}
				// We'll be handling errors anew
				self::$handling_errors = false;
			} catch (Pie_Exception_DispatcherErrors $e) {
				if (!empty($ob)) {
					$partial_response = $ob->getClean();
				} else {
					$partial_response = null;
				}
				self::errors(null, $module, $partial_response);
				self::result("Rendered errors");
				return true;	
			} catch (Exception $exception) {
				if (!empty($ob)) {
					$partial_response = $ob->getClean();
				} else {
					$partial_response = null;
				}
				self::errors($exception, $module, $partial_response);
				self::result("Exception occurred");
				return false;
			}
		}
		
		// If we are here, we have done forwarding too much
		throw new Pie_Exception_Recursion(array(
			'function_name' => 'Dispatcher::forward()'
		));
	}
	
	protected static function errors(
		$exception, 
		$module, 
		$partial_response = null)
	{
		// In the handlers below, you can get errors with:
		// $errors = Pie_Response::getErrors();

		try {
			if (self::$handling_errors) {
				// We need to handle errors, but we
				// have already tried to do it.
				// Just show the errors view.
				echo Pie::view('pie/errors.php', compact('errors'));
				return;
			}
			self::$handling_errors = true;
		
			if (Pie::canHandle("$module/errors")) {
				Pie::event("$module/errors", 
				 compact('errors', 'exception', 'partial_response'));
			} else {
				Pie::event("pie/errors", 
				 compact('errors', 'exception', 'partial_response'));
			}
		} catch (Exception $e) {
			Pie::event('pie/exception', array('exception' => $e));
		}
	}
	
	protected static $uri;
	protected static $skip = array();
	protected static $handling_errors = false;
}

