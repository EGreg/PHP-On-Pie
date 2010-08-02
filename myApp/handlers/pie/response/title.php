<?php

function pie_response_title()
{
	// The default title
	$title = Pie_Config::get('pie', 'app', basename(APP_DIR));
	$action = Pie_Dispatcher::uri()->action;
 	if ($action) {
		$title .= ": $action";
	}
	return $title;
}
