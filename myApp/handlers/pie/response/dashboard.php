<?php

function pie_response_dashboard()
{	
	$app = Pie_Config::expect('pie', 'app');
	$slogan = "Powered by PHP ON PIE.";
	return Pie::view("$app/dashboard.php", compact('slogan'));
}
