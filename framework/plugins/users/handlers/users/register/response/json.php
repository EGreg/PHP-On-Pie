<?php

function users_register_response_json()
{
	$user = Pie::ifset(Users::$cache['user']);
	unset($user->password_hash);
	return compact('user');
}