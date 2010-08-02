<?php

function pie_post($params)
{
	$uri = Pie_Dispatcher::uri();
	$module = $uri->module;
	$action = $uri->action;
	if (!Pie::canHandle("$module/$action/post")) {
		return null;
	}
	return Pie::event("$module/$action/post", $params);
}
