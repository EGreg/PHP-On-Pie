<?php

/**
 * Default pie/response handler.
 * 1. Gets some slots, depending on what was requested.
 * 2. Renders them in a layout
 *    The layout expects "title", "dashboard" and "contents" slots to be filled.
 */
function pie_response($params)
{
	extract($params);
	/**
	 * @var Exception $exception
	 * @var array $errors
	 */
	
	// Redirect to success page, if requested.
	$is_ajax = Pie_Request::isAjax();
	if (empty($errors) and empty($exception)) {
		if (!$is_ajax and isset($_REQUEST['_pie']['onSuccess'])) {
			$on_success = $_REQUEST['_pie']['onSuccess'];
			if (Pie_Config::get('pie', 'response', 'onSuccessShowFrom', true)) {
				$on_success = Pie_Uri::url($on_success.'?_pie[fromSuccess]='.Pie_Dispatcher::uri());
			}
			Pie_Response::redirect($on_success);
			return;
		}
	}
	
	// Get the requested module
	$uri = Pie_Dispatcher::uri();
	if (!isset($module)) {
		$module = $uri->module;
		if (!isset($module)) {
			$module = 'pie';
			Pie_Dispatcher::uri()->module = 'pie';
		}
	}
	
	// Get the main module (the app)
	$app = Pie_Config::expect('pie', 'app');
	
	// Add some javascript to inform the front end of important URLs	
	Pie::event('pie/addScriptLines');

	// What to do if this is an AJAX request
	if ($is_ajax) {
		$slot_names = Pie_Request::slotNames();
		if (!isset($slot_names)) {
			$slot_names = Pie_Config::get(
				$module, 'response', 'slotNames',
				array(
					'content'=>null, 
					'dashboard'=>null, 
					'title'=>null,
					'notices'=>null
				)
			);
		}
		$slots = array();
		$stylesheets = array();
		$stylesInline = array();
		$scripts = array();
		$scriptLines = array();
		if (is_array($slot_names)) {
			foreach ($slot_names as $slot_name => $v) {
				$slots[$slot_name] = Pie_Response::fillSlot($slot_name, 'default');
				$stylesheets[$slot_name] = Pie_Response::stylesheetsArray($slot_name);
				$stylesInline[$slot_name] = Pie_Response::stylesInline($slot_name);
				$scripts[$slot_name] = Pie_Response::scriptsArray($slot_name);
				$scriptLines[$slot_name] = Pie_Response::scriptLines($slot_name);
			}
		}
		$timestamp = microtime(true);
		$echo = Pie_Request::contentToEcho();
		
		// Render a JSON layout for ajax
		$to_encode = compact(
			'slots', 
			'stylesheets', 'stylesInline', 
			'scripts', 'scriptLines', 
			'timestamp', 'echo'
		);
		
		// Cut down on the response size
		foreach (array(
		 'slots', 'stylesheets', 'stylesInline', 'scripts', 'scriptLines'
		) as $f) {
			$is_empty = true;
			if (is_array($to_encode[$f])) {
				foreach ($to_encode[$f] as $k => $v) {
					if (isset($v)) {
						$is_empty = false;
					} else {
						unset($to_encode[$f][$k]);
					}
				}
			} else if (!empty($to_encode[$f])) {
				$is_empty = false;
			}
			if ($is_empty) {
				unset($to_encode[$f]);
			}
		}
		switch (strtolower($is_ajax)) {
		case 'json':
		default:
			$json = json_encode($to_encode);
			$callback = Pie_Request::callback();
			echo $callback ? "$callback($json)" : $json;
		}
		return;
	}

	// If this is a request for a regular webpage,
	// fill the usual slots and render a layout.
	
	// Attach stylesheets and scripts
	if (Pie_Request::accepts('text/fbml')) {
		Pie_Response::addStylesheet("css/fbml.css");
		Pie_Response::addScript('plugins/pie/fbjs/Pie.fb.js');
	} else {
		Pie_Response::addStylesheet("css/html.css");
		Pie_Response::addScript('plugins/pie/js/Pie.js');
	}

	// Get all the usual slots for a webpage
	$slot_names = Pie_Config::get(
		$module, 'response', 'slotNames',
		array(
			'content'=>null, 
			'dashboard'=>null, 
			'title'=>null,
			'notices'=>null
		)
	);
	$slots = array();
	foreach ($slot_names as $sn => $v) {
		$slots[$sn] = Pie_Response::fillSlot($sn, 'default');
	}
	
	$output = Pie_Response::output();
	if (isset($output)) {
		if ($output === true) {
			return;
		}
		if (is_string($output)) {
			echo $output;
		}
		return;
	}

	if (Pie_Request::accepts('text/fbml')) {
		// Render a full FBML layout
		$layout_view = Pie_Config::get(
			$app, 'response', 'layout_fbml', 
			"$app/layout/fbml.php"
		);
		echo Pie::view($layout_view, $slots);
	} else {
		// Render a full HTML layout
		$layout_view = Pie_Config::get(
			$app, 'response', 'layout_html', 
			"$app/layout/html.php"
		);
		echo Pie::view($layout_view, $slots);
	}
}
