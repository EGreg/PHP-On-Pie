<?php

/**
 * Static methods for the users models.
 * This description should be revised and expanded.
 *
 * @package users
 */
abstract class Users extends Base_Users
{	
	/**
	 * The facebook object that would be instantiated
	 * during the "pie/objects" event, if the request
	 * warrants it.
	 */
	static $facebook = null;
	
	/**
	 * Facebook objects that would be instantiated
	 * from cookies during the "pie/objects" event,
	 * if there are cookies for them.
	 */
	static $facebooks = array();
	
	/**
	 * Retrieves the currently logged-in user from the session.
	 * If the user was not originally retrieved from the database,
	 * inserts a new one.
	 * Thus, this can also be used to turn visitors into registered
	 * users.
	 *
	 * @param string $platform
	 *  Currently only supports the value "facebook".
	 * @param int $app_id
	 *  The id of the app within the specified platform.
	 *  Used for storing app-specific session information.
	 * @param string $password
	 *  Sometimes, if the authentication isn't to be trusted,
	 *  a password is required. Pass true here to bypass
	 *  password checking and log in the user regardless.
	 *  Otherwise, this string is hashed and tested.
	 */
	static function authenticate(
		$platform, 
		$app_id = null,
		$password = null)
	{
		if (!isset($app_id)) {
			$app = Pie_Config::expect('pie', 'app');
			$app_id = Pie_Config::expect('users', 'facebookApps', $app, 'appId');
		}
		
		$return = null;
		$return = Pie::event('users/auth', compact('platform', 'app_id', 'password'), 'before');
		if (isset($return)) {
			return $return;
		}
		
		if (!isset($platform) or $platform != 'facebook') {
			throw new Pie_Exception_WrongType(array('field' => 'platform', 'type' => '"facebook"'));
		}
		
		if (!isset($app_id)) {
			throw new Pie_Exception_WrongType(array('field' => 'app_id', 'type' => 'integer'));
		}
		
		Pie_Session::start();
		
		// First, see if we've already logged in somehow
		if ($user = self::loggedInUser()) {
			// Get logged in user from session
			$user_was_logged_in = true;
		} else {
			// Get an existing user or create a new one
			$user = new Users_User();
			switch ($platform) {
			case 'facebook':
				if (!Users::facebook($app_id)) {
					return false;
				}
				if (!Users::facebook($app_id)->user) {
					return false; // no one logged in
				}
				$user->fb_uid = Users::facebook($app_id)->user;
				break;
			default:
				return false; // not sure how to log this user in
			}
			$retrieved = $user->retrieve();
			
			// Now save this user in the session as the logged-in user
			self::setLoggedInUser($user);
			$session_id = Pie_Session::id();
			
			if ($retrieved) {
				// User exists in database. Do we need to update it?
				if (!isset($user->session_key)
				 or $user->session_key != $session_id) {
					Pie::event('users/authUpdateUser', compact('user'), 'before');
					$user->session_key = $session_id;
					$user->save(); // update session_key in user
					Pie::event('users/authUpdateUser', compact('user'), 'after');
				}
			} else {
				// No user in database. Will insert a new one!
				// These users might be shared across apps.
				Pie::event('users/authInsertUser', compact('user'), 'before');
				$user->session_key = $session_id;
				$user->save();
				$_SESSION['users']['user'] = $user;
				Pie::event('users/authInsertUser', compact('user'), 'after');
				$inserted_new_user = true;
			}
		}
		
		// Now make sure our master session contains the
		// session info for the platform app.
		if ($platform == 'facebook') {
			$fb_prefix = 'fb_sig_';
			if (!Users::facebook($app_id)) {
				return false;
			}
			$facebook = Users::facebook($app_id);
			
			if (isset($_SESSION['users']['facebookAppUser'][$app_id])) {
				// Facebook app user exists. Do we need to update it? (Probably not!)
				$app_user = $_SESSION['users']['facebookAppUser'][$app_id];
				$app_user->state = 'added';
				
				if (!isset($app_user->session_key)
				 or $facebook->api_client->session_key != $app_user->session_key) {
					Pie::event('users/authUpdateAppUser', compact('user'), 'before');
					$app_user->session_key = $facebook->api_client->session_key;
					$app_user->save(); // update session_key in app_user
					Pie::event('users/authUpdateAppUser', compact('user'), 'after');
				}
			} else {
				// We have to put the session info in
				$app_user = new Users_AppUser();
				$app_user->user_id = $user->id;
				$app_user->platform = 'facebook';
				$app_user->app_id = $app_id;
				if ($app_user->retrieve()) {
					// App user exists in database. Do we need to update it?
					if (!isset($app_user->session_key)
					 or $app_user->session_key != $facebook->api_client->session_key) {
						Pie::event('users/authUpdateAppUser', compact('user'), 'before');
						$app_user->session_key = $facebook->api_client->session_key;
						$app_user->save(); // update session_key in app_user
						Pie::event('users/authUpdateAppUser', compact('user'), 'after');
					}
				} else {
					$app_user->state = 'added';
					$app_user->session_key = $facebook->api_client->session_key;
					$app_user->platform_uid = $user->fb_uid;
					Pie::event('users/authInsertAppUser', compact('user'), 'before');
					$app_user->save();
					Pie::event('users/authInsertAppUser', compact('user'), 'after');
				}
			}
			
			$_SESSION['users']['facebookAppUser'][$app_id] = $app_user;
		}
		
		Pie::event('users/auth', compact('platform', 'app_id', 'password'), 'after');
		
		// At this point, $user is set.
		return $user;
	}
	
	/**
	 * Logs a user in using a login identifier and a pasword
	 * @param string $identifier
	 *  Could be an email address, a mobile number, or a user id.
	 * @param string $password
	 *  The password to hash, etc.
	 */
	static function login(
		$identifier,
		$password)
	{	
		$return = null;
		$return = Pie::event('users/login', compact('identifier', 'password'), 'before');
		if (isset($return)) {
			return $return;
		}
		
		Pie_Session::start();
		$session_id = Pie_Session::id();

		// First, see if we've already logged in somehow
		if ($user = self::loggedInUser()) {
			// Get logged in user from session
			return $user;
		}

		$user = new Users_User();
		$user->identifier = $identifier;
		if ($user->retrieve()) {
			// User exists in database. Now check the password.
			$password_hash = self::hashPassword($password, $user->password_hash);
			if ($password_hash != $user->password_hash) {
				// Passwords don't match!
				throw new Users_Exception_WrongPassword(compact('identifier'));
			}
			
			// Do we need to update it?
			if (!isset($user->session_key)
			 or $user->session_key != $session_id) {
				Pie::event('users/loginUpdateUser', compact('user'), 'before');
				$user->session_key = $session_id;
				$user->save(); // update session_key in user
				Pie::event('users/loginUpdateUser', compact('user'), 'after');
			}
		} else {
			// No user in database. Will insert a new one!
			// These users might be shared across apps.
			$user->password_hash = self::hashPassword($password);
			$user->session_key = $session_id;
			Pie::event('users/loginInsertUser', compact('user'), 'before');
			$user->save();
			Pie::event('users/loginInsertUser', compact('user'), 'after');
			$inserted_new_user = true;
		}
		
		// Now save this user in the session as the logged-in user
		self::setLoggedInUser($user);
		
		Pie::event('users/login', compact(
			'identifier', 'password', 'inserted_new_user', 'user'
		), 'after');
	}
	
	static function logout()
	{
		// Access the session, if we haven't already.
		Pie_Session::start();
		
		// One last chance to do something.
		// Hooks shouldn't be able to cancel the logout.
		Pie::event('users/logout', array(), 'before');
		
		// Clear out the session.
		if (isset($_SESSION)) {
			foreach ($_SESSION as $k => $v) {
				unset($_SESSION[$k]);
			}
		}
		session_write_close();
	}
	
	/**
	 * Get the logged-in user's information
	 * @return Users_User
	 */
	static function loggedInUser(
		$related_class_name = null)
	{
		Pie_Session::start();
		if (!isset($_SESSION['users']['user'])) {
			return null;
		}
		$user = $_SESSION['users']['user'];
		if (!$related_class_name) {
			return $user;
		}
		
		static $email = null;
		static $zipcode = null;
		switch ($related_class_name) {
		 case 'email':
			if (isset($email)) {
				return $email;
			}
			$email = new Users_Email();
			$email->user_id = $user;
			$email = $email->retrieve();
			return $email;
		 case 'zipcode':
			if (isset($zipcode)) {
				return $zipcode;
			}
			$zipcode = new Users_Zipcode();
			$zipcode->zipcode = $user->zipcode;
			$zipcode = $zipcode->retrieve();
			return $zipcode;
		}
	}
	
	/**
	 * Use with caution! This bypasses authentication.
	 * This functionality should not be exposed externally.
	 * @param Users_User $user
	 *  The user object
	 */
	static function setLoggedInUser($user)
	{		
		if (isset($_SESSION['users']['user']->id)) {
			if ($user->id == $_SESSION['users']['user']->id) {
				// This user is already the logged-in user.
				return;
			}
		}
		
		// Change the session id to prevent session fixation attacks
		Pie_Session::regenerate_id();
		
		// Store the new information in the session
		$snf = Pie_Config::get('pie', 'session', 'nonceField', 'nonce');
		$_SESSION['users']['user'] = $user;
		$_SESSION['pie'][$snf] = uniqid();
		
		Pie::event('users/setLoggedInUser', compact('user'), 'after');
	}
	
	static function platformAppUser($platform, $app_id)
	{
		if (!isset($platform) or $platform != 'facebook') {
			throw new Pie_Exception_WrongType(array('field' => 'platform', 'type' => '"facebook"'));
		}
		
		if (!isset($app_id)) {
			throw new Pie_Exception_WrongType(array('field' => 'app_id', 'type' => 'integer'));
		}

		if (!isset($_SESSION['users']['facebookAppUser'][$app_id])) {
			return null;
		}
		return $_SESSION['users']['facebookAppUser'][$app_id];
	}
	
	/**
	 * Hashes a password
	 * @param string $password
	 *  the password to hash
	 * @param string $existing_hash
	 *  must provide when password hash has been already stored.
	 *  It contains the salt for the password.
	 * @return string
	 *  the hashed password
	 */
	static function hashPassword ($password, $existing_hash = null)
	{
		$hash_function = Pie_Config::get(
			'users', 'password', 'hashFunction', 'sha1'
		);
		$password_hash_iterations = Pie_Config::get(
			'users', 'password', 'hashIterations', 1103
		);
		$salt_length = Pie_Config::get(
			'users', 'password', 'saltLength', 0
		);
		
		if ($salt_length > 0) {
			if (empty($existing_hash)) {
				$salt = substr(sha1(uniqid(mt_rand(), true)), 0, 
					$salt_length);
			} else {
				$salt = substr($existing_hash, - $salt_length);
			}
		}
		
		$salt2 = isset($salt) ? '_'.$salt : '';
		$result = $password;
	
		// custom hash function
		if (!is_callable($hash_function)) {
			throw new Pie_Exception_MissingFunction(array(
				'function_name' => $hash_function
			));
		}
		$confounder = $password . $salt2;
		$confounder_len = strlen($confounder);
		for ($i = 0; $i < $password_hash_iterations; ++$i) {
			$result = call_user_func(
				$hash_function, 
				$result . $confounder[$i % $confounder_len]
			);
		}
		$result .= $salt2;
			
		return $result;
	}
	
	/**
	 * Get the status of the logged-in user and their account.
	 * @param Users_Email $email
	 *  Optional. Pass a reference here to be filled with the email object, if it's loaded.
	 *  You can use it in conjunction with the "verify email" status.
	 * @return array|boolean
	 *  Returns false if the user is not logged in.
	 *  Returns true if everything is complete.
	 *  Otherwise, returns an array whose keys are the names of the missing fields:
	 *  ("first_name", "last_name", "birthday", "gender", "desired_gender", "username",
	 *  "email_address")
	 *  and the values are "missing" or "unverified"
	 */
	static function accountStatus(&$email = null)
	{
		$module = Pie_Dispatcher::uri()->module;
		$user = Users::loggedInUser();
		if (!$user) {
			// Try to authenticate
			$app_id = Pie_Config::expect('users', 'facebookApps', $module, 'appId');
			$user = Users::authenticate('facebook', $app_id);
			if (!$user) {
				return false;
			}
		}
		$result = array();
		if (empty($user->email_address)) {
			// An email address isn't verified for this user yet.
			// If the user hasn't even added an email address, then ask for one.
			if (!isset(self::$email)) {
				self::$email = new Users_Email();
				self::$email->user_id = $user->id;
				self::$email = self::$email->retrieve(null, false, '*', true)
					->orderBy('time_created', false)->resume();
			}
			$email = self::$email;
			if ($email) {
				// The email could be unverified, sunspended, unsubscribed, etc.
				$result['email_address'] = $email->state;
			} else {
				$result['email_address'] = 'missing';
			}
		}
		$fieldnames = array(
			'first_name', 'last_name', 'username',
			'birthday', 'gender', 'desired_gender',
			'relationship_status', 'relationship_user_id', 'zipcode',
		);
		foreach ($fieldnames as $k => $v) {
			if (empty($user->$v)) {
				$result[$v] = 'missing';
			}
		}

		return $result;
	}
	
	static function facebook($key)
	{
		if (!isset($key)) {
			if (isset(self::$facebook)) {
				return self::$facebook;
			}
		}
		if (isset(self::$facebooks[$key])) {
			return self::$facebooks[$key];
		}
		
		$fb_prefix = 'fb_sig_';

		// Get the facebook object from POST, if any
		if (isset($_POST[$fb_prefix.'app_id'])) {
			$app_id = $_POST[$fb_prefix.'app_id'];
			$fb_apps = Pie_Config::get('users', 'facebookApps', array());
			$fb_info = null;
			$fb_key = null;
			foreach ($fb_apps as $key => $a) {
				if (isset($a['appId']) and $a['appId'] == $app_id) {
					$fb_info = $a;
					$fb_key = $key;
					break;
				}
			}
			if (isset($fb_info['apiKey']) && isset($fb_info['secret'])) {
				$facebook = new Facebook($fb_info['apiKey'], $fb_info['secret']);
				Users::$facebook = $facebook;
				Users::$facebooks[$app_id] = $facebook;
				Users::$facebooks[$key] = $facebook;
				return $facebook;
			}
		}

		$fb_info = Pie_Config::get('users', 'facebookApps', $key, array());
		if ($fb_info) {
			if (isset($_COOKIE[$fb_info['apiKey'].'_user'])
			and isset($_COOKIE[$fb_info['apiKey'].'_session_key'])) {
				$facebook = new Facebook(
					$fb_info['apiKey'],
					$fb_info['secret']
				);
				$facebook->set_user(
					$_COOKIE[$fb_info['apiKey'].'_user'],
					$_COOKIE[$fb_info['apiKey'].'_session_key']
				);
				Users::$facebooks[$fb_info['appId']] = $facebook;
				Users::$facebooks[$key] = $facebook;
			}
			return $facebook;
		}
		
		// Otherwise, this facebook object isn't there
		return null;
	}
	
	static function fql($facebook, $fql_query)
	{
		$ret = Pie::event('users/fql', compact('facebook', 'fql_query'), 'before');
		if (isset($ret)) {
			return $ret;
		}
		if (!($facebook instanceof Facebook)) {
			throw new Pie_Exception_WrongType(array(
				'field' => '$facebook', 'type' => 'Facebook'
			));
		}
		if (!array_key_exists($fql_query, self::$fql_results)) {
			$results = $facebook->api_client->fql_query($fql_query);
			self::$fql_results[$fql_query] = $results;
		} else {
			$results = self::$fql_results[$fql_query];
		}
		Pie::event('users/fql', compact('facebook', 'fql_query', 'results'), 'after');
		return $results;
	}
	
	static function facebookFriends($facebook, $uid = null)
	{
		if (!($facebook instanceof Facebook)) {
			throw new Pie_Exception_WrongType(array(
				'field' => '$facebook', 'type' => 'Facebook'
			));
		}
		if (!isset($uid)) {
			$uid = $facebook->user;
			if (!isset($uid)) {
				throw new Users_Exception_NotLoggedIn();
			}
		}
		$q = "SELECT uid, first_name, last_name, "
			. "pic_small, pic_big, pic_square, pic, birthday_date, "
			. "sex, meeting_sex, religion "
			. "FROM user WHERE uid IN "
			. "(SELECT uid2 FROM friend WHERE uid1 = $uid)"
			. "OR uid = $uid";
		return self::fql($facebook, $q);
	}

	protected static $fql_results = array();
	protected static $email = null; // cached
	
	public static $cache = array();
};
