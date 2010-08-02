<?php

/**
 * The default implementation.
 */
function pie_errors($params) {
	extract($params);
	/**
	 * @var Exception $exception
	 */

	if (!empty($exception)) {
		Pie_Response::addError($exception);
		$errors = Pie_Response::getErrors();
	}

	$errors_array = Pie_Exception::toArray($errors);
	$exception_array = Pie_Exception::toArray($exception);

	// Simply return the errors, if this was an AJAX request
	if ($is_ajax = Pie_Request::isAjax()) {
		switch (strtolower($is_ajax)) {
		case 'json':
		default:
			$json = json_encode(array(
				'errors' => $errors_array, 
				'exception' => $exception_array
			));
			$callback = Pie_Request::callback();
			echo $callback ? "$callback($json)" : $json;
		}
		return;
	}

	// Forward internally, if it was requested
	if (isset($_REQUEST['_pie']['onErrors'])) {
		$uri = Pie_Dispatcher::uri();
		$uri2 = Pie_Uri::from($_REQUEST['_pie']['onErrors']);
		if ($uri !== $uri2) {
			Pie_Dispatcher::forward($uri2);
			return; // we don't really need this, but it's here anyway
		}
	}
	
	if (Pie::eventStack('pie/response')) {
		// Errors happened while rendering response. Just render errors view.
		return Pie::view('pie/errors.php', $params);
	}

	try {
		// Try rendering the response, expecting it to
		// display the errors along with the rest.
		$ob = new Pie_OutputBuffer();
		Pie::event('pie/response', compact(
			'errors', 'exception',
			'errors_array', 'exception_array'
		));
		$ob->endFlush();
	} catch (Exception $exception) {
		$output = $ob->getClean();
		return Pie::event('pie/exception', compact('exception'));
	}
}
