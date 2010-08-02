<?php

function users_user_response_json()
{
	$email_address = $_REQUEST['email_address'];

	// check our db
	$user = new Users_User();
	$user->email_address = $email_address;
	if ($user->retrieve()) {
		return array(
			'username' => $user->username,
			'icon' => $user->icon
		);
	}
	
	$email_hash = md5(strtolower(trim($email_address)));
	$json = file_get_contents("http://www.gravatar.com/$email_hash.json");
	$result = json_decode($json);
	if ($result) {
		return $result;
	}
	
	// otherwise, return default
	$email_parts = explode('@', $email_address, 2);
	return array("entry" => array(array(
		"id" => "571",
		"hash" => "357a20e8c56e69d6f9734d23ef9517e8",
		"requestHash" => "357a20e8c56e69d6f9734d23ef9517e8",
		"profileUrl" => "http:\/\/gravatar.com\/test",
		"preferredUsername" => $email_parts[0],
		"thumbnailUrl" => "http://gravatar.com/avatar/$email_hash?r=g&d=wavatar&s=80",
		"photos" => array(),
		"displayName" => "",
		"urls" => array()
	)));
}