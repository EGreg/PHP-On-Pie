<?php

/*
 * This is the slot filled by the typical
 * "pie/response" handler when the requested
 * slot returns null.
 */
function pie_response_default($params)
{
	if (!isset($params['slot_name'])) {
		throw new Pie_Exception_RequiredField(array(
			'field' => '$slot_name'
		));
	}
	$slot_name = $params['slot_name'];
	$uri = Pie_Dispatcher::uri();
	$module = $uri->module;
	$action = $uri->action;
	if (Pie::canHandle("$module/$action")) {
		Pie::event("$module/$action");
	}
	$event = "$module/$action/response/$slot_name";
	if (Pie::canHandle($event)) {
		return Pie::event($event);
	}
	return "Need to define $event";
}
