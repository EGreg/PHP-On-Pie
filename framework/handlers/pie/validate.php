<?php

function pie_validate($params)
{
	$uri = Pie_Dispatcher::uri();
	$module = $uri->module;
	$action = $uri->action;
	if (!Pie::canHandle("$module/$action/validate")) {
		return null;
	}
	return Pie::event("$module/$action/validate", $params);
}
