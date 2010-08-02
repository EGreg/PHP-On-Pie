<?php

function pie_tool_zoomer($fields)
{
	Pie_Response::addScript('plugins/pie/js/PieTools.js');
	Pie_Response::addStylesheet('plugins/pie/css/Pie.css');
	return Pie::view('pie/tool/zoomer.php');
}
