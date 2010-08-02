<?php

function users_activate_response_content()
{
	$uri = Pie_Dispatcher::uri();
	$email_address = $uri->email_address;
	$mobile_number = $uri->mobile_number;
	if ($uri->email_address) {
		$type = 'email address';
	} else if ($uri->mobile_number) {
		$type = 'mobile_number';
	} else {
		$type = '';
	}
	$user = Pie::ifset(Users::$cache['user'], false);
	return Pie::view('users/content/activate.php', compact(
		'email_address', 'mobile_number', 'type','user'
	));
}
