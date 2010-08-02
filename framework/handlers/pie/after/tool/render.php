<?php

function pie_after_tool_render($params, &$result)
{	
	$tool_name = $params['tool_name'];
	$fields = $params['fields'];
	$pie_options = $params['pie_options'];

	if (empty($pie_options['inner'])) {
		$classes = isset($pie_options['classes'])
			? ' ' . $pie_options['classes']
			: '';
		$p = implode('_', explode('/', $tool_name)) . '_';
		$result = "<!--\n\nstart tool $tool_name\n\n-->"
		 . Pie_Html::div('tool', "pie_tool {$p}tool$classes")
		 . $result 
		 . "</div><!--\n\nend tool $tool_name \n\n-->";
	}
	
	$prefix = Pie_Html::pieIdPrefix();
}
