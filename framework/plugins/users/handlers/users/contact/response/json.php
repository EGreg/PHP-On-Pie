<?php

function users_contact_response_json()
{
	$user = Users::loggedInUser();
	if (!$user) {
		return array();
	}
	return $user;
}