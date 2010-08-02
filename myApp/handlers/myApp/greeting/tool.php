<?php

function myApp_greeting_tool($fields)
{
	$defaults = array('greeting' => 'Default greeting');
	extract(array_merge($defaults, $fields));
	return '<h1 class="myApp_greeting_tool tool">' . Pie_Html::text($greeting) . '</h1>';
}
