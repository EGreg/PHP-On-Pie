<?php

function pie_before_tool_render($params, &$result)
{
	static $prefix_was_rendered = array();
	static $temp_id = 0;
	
	$tool_name = $params['tool_name'];
	$pie_options = $params['pie_options'];
	$prefix = implode('_', explode('/', $tool_name)) . '_';
	$id = isset($pie_options['id']) ? $pie_options['id'] : '';
	if (!empty($id)) {
		$prefix = 'id'.$id.'_'.$prefix;
	}
	
	if (isset($pie_options['prefix'])) {
		$cur_prefix = $pie_options['prefix'];
	} else {
		$cur_prefix = Pie_Html::getIdPrefix();
	}
	$tool_prefix = $cur_prefix . $prefix;
	
	if (isset($prefix_was_rendered[$tool_prefix])) {
		trigger_error("A tool with prefix \"$tool_prefix\" was already rendered.", E_USER_NOTICE);
	}
	$prefix_was_rendered[$tool_prefix] = true;
	$prev_prefix = Pie_Html::pushIdPrefix($tool_prefix);

	// other parameters:
	// script, notReady, id
	$pie_prefix = $prefix;
	$result = compact('pie_prefix');
}
