<?php

function users_account_post()
{
	Pie_Session::start();
	Pie_Valid::nonce(true);

	extract($_REQUEST);

	// Implement the action

	$user = Users::loggedInUser();
	if (!$user) {
		throw new Users_Exception_NotLoggedIn();
	}

	/*
      if (!isset($gender) and isset($user->gender)) {
              $gender = $user->gender;                                                                                        
      }
      if (isset($orientation)) {
              if (isset($gender) and $orientation == 'straight') {
                      $desired_gender = ($gender == 'male') ? 'female' : 'male';
              } else if (isset($gender) and $orientation == 'gay') {
                      $desired_gender = $gender;
              } else {
                      $desired_gender = 'either';
              }
      }

      if (isset($first_name)) $user->first_name = $first_name;
      if (isset($last_name)) $user->last_name = $last_name;
      if (isset($gender)) $user->gender = $gender;
      if (isset($desired_gender)) $user->desired_gender = $desired_gender;
      if (isset($username)) $user->username = $username;
      if (isset($relationship_status)) {
              $user->relationship_status = $relationship_status;
      }
      if (isset($birthday_year)) {
              $user->birthday = date("Y-m-d", mktime(
                      0, 0, 0, $birthday_month, $birthday_day, $birthday_year
              ));
      }
      if (isset($zipcode)) $user->zipcode = $zipcode;

		$user->save(true);
	*/
	
      // the $_SESSION['users']['user'] is now altered
}
