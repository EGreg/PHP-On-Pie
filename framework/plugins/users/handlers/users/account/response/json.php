<?php

function users_account_response_json()
{
	$user = Users::loggedInUser();
	if (!$user) {
		return array();
	}
	return $user;
}