<?php

function pie_exception($params)
{
	extract($params);
	/**
	 * @var Exception $exception 
	 */

	$message = $exception->getMessage();
	$file = $exception->getFile();
	$line = $exception->getLine();
	if ($is_ajax = Pie_Request::isAjax()) {
		// Render a JSON layout for ajax
		switch (strtolower($is_ajax)) {
		case 'json':
		default:
			$json = json_encode(array(
				'errors' => Pie_Exception::toArray(array($exception))
			));
			$callback = Pie_Request::callback();
			echo "$callback($json)";
		}
	} else {
		if (is_callable(array($exception, 'getTraceAsStringEx'))) {
			$trace_string = $exception->getTraceAsStringEx();
		} else {
			$trace_string = $exception->getTraceAsString();
		}
		if (Pie::textMode()) {
			$result = "$message\n" 
			 . "in $file ($line)\n" 
			 . $trace_string;
		} else {
			if (($exception instanceof Pie_Exception_PhpError) or !empty($exception->messageIsHtml)) {
				// do not sanitize $message
			} else {
				$message = Pie_Html::text($message);
			}
			$result = "<h1>$message</h1>"
			 . "<h3>in $file ($line)</h3>"
			 . "<pre>"
			 . $trace_string
			 . "</pre>";
		}
		echo $result;
	}
	$app = Pie_Config::get('pie', 'app', null);
	Pie::log("$app: Exception in " . ceil(Pie::microtime()) . "ms\n" );
	Pie::log("$message\n  in $file ($line)");
}
