<?php

/**
 * Class representing user rows.
 * You can create an object of this class either to
 * access its non-static methods, or to actually
 * represent a user row in the users database.
 *
 * This description should be revised and expanded.
 *
 * @package users
 */
class Users_User extends Base_Users_User
{
	/**
	 * The setUp() method is called the first time
	 * an object of this class is constructed.
	 */
	function setUp()
	{
		parent::setUp();
	}
	
	function age()
	{
		if (empty($this->birthday)) {
			return null;
		}
		list($year,$month,$day) = explode("-",$this->birthday);
		$year_diff  = date("Y") - $year;
		$month_diff = date("m") - $month;
		$day_diff   = date("d") - $day;
		if ($month_diff < 0) {
			$year_diff--;
		} else if ($month_diff == 0 && $day_diff < 0) {
			$year_diff--;
		}
		return $year_diff;
	}
	
	function beforeSet_username($username)
	{
		parent::beforeSet_username($username);
		if (!isset($username)) {
			return array('username', $username);
		}
		Pie::event(
			'users/validate/username',
			array('username' => & $username)
		);
		return array('username', $username);
	}
	
	function beforeSet_zipcode($zipcode)
	{
		parent::beforeSet_zipcode($zipcode);
		if (!isset($zipcode)) {
			return array('zipcode', $zipcode);
		}
		$zipcode_valid = true;
		if (strlen($zipcode) != 5) {
			$zipcode_valid = false;
		} else {
			$row = Users_User::db()
				->select('COUNT(1)', Users_Zipcode::table())
				->where(array('zipcode' => $zipcode))
				->limit(1)
				->execute()->fetch();
			if ($row[0] == 0) {
				$zipcode_valid = false;
			}
		}
		if (!$zipcode_valid) {
			$field = 'Zipcode';
			$range = 'a valid zipcode';
			throw new Pie_Exception_WrongValue(compact('field', 'range'), 'zipcode');
		}
		Pie::event(
			'users/validate/zipcode',
			array('zipcode' => & $zipcode)
		);
		return array('zipcode', $zipcode);
	}
	
	function beforeSet_email_address($email_address)
	{
		parent::beforeSet_email_address($email_address);
		Pie::event(
			'users/validate/email_address',
			array('email_address' => & $email_address)
		);
		if (!isset($email_address)) {
			return array('email_address', $email_address);
		}
		return array('email_address', $email_address);
	}
	
	function beforeSave($updated_fields)
	{
		parent::beforeSave($updated_fields);
		if (isset($updated_fields['username'])) {
			$app = Pie_Config::expect('pie', 'app');
			$unique = Pie_Config::get('users', 'model', $app, 'username_unique', true);
			if ($unique) {
				$criteria = array(
					'username' => $updated_fields['username']
				);
				if (isset($this->id)) {
					$criteria['id != '] = $this->id;
				}
				$row = Users_User::db()
					->select('COUNT(1)', Users_User::table())
					->where($criteria)->limit(1)
					->execute()->fetch();
				if ($row[0] > 0) {
					throw new Users_Exception_UsernameExists(null, 'username');
				}
			}
		}
		return $updated_fields;
	}
	
	/**
	 * Starts the process of adding an email to a saved user object.
	 * Also modifies and saves this user object back to the database.
	 * @param string $email_address
	 *  The email address to add.
	 * @param string $activation_email_subject
	 *  The subject of the activation email to send.
	 * @param string $activation_email_view
	 *  The view to use for the body of the activation email to send.
	 * @param boolean $html
	 *  Defaults to true. Whether to send as HTML email.
	 * @param array $fields
	 *  An array of additional fields to pass to the email view.
	 * @return boolean
	 *  Returns true on success.
	 *  Returns false if this email address is already verified for this user.
	 * @throws Pie_Exception_WrongType
	 *  If the email address is in an invalid format, this is thrown.
	 * @throws Users_Exception_AlreadyVerified
	 *  If the email address already exists and has been verified for
	 *  another user, then this exception is thrown.
	 */
	function addEmail(
		$email_address,
		$activation_email_subject = null,
		$activation_email_view = null,
		$html = true,
		$fields = array())
	{
		if (!Pie_Valid::email($email_address)) {
			throw new Pie_Exception_WrongValue(array(
				'field' => 'Email', 
				'range' => 'a valid address'
			), 'email_address');
		}
		Pie::event(
			'users/validate/email_address',
			array('email_address' => & $email_address)
		);
		$e = new Users_Email();
		$e->address = $email_address;
		if ($e->retrieve() and $e->state !== 'unverified') {
			if ($e->user_id === $this->id) {
				return false;
			}
			// Otherwise, say it's verified for another user,
			// even if it unsubscribed or was suspended.
			throw new Users_Exception_AlreadyVerified(array(
				'key' => $e->address,
				'user_id' => $e->user_id
			), 'email_address');
		}
		
		// If we are here, then the email record either
		// doesn't exist, or hasn't been verified yet.
		// In either event, update the record in the database,
		// and re-send the email.
		$minutes = Pie_Config::get('users', 'activationCodeExpires', 60*24*7);
		$e->state = 'unverified';
		$e->user_id = $this->id;
		$e->activation_code = Pie_Utils::unique(5);
		$e->activation_code_expires = new Db_Expression(
			"CURRENT_TIMESTAMP + INTERVAL $minutes MINUTE"
		);
		$e->auth_code = md5(microtime() + mt_rand());
		$e->save();
		
		if (!isset($activation_email_view)) {
			$activation_email_view = Pie_Config::get(
				'users', 'activationEmailView', 'users/email/activation.php'
			);
		}
		if (!isset($activation_email_subject)) {
			$activation_email_subject = Pie_Config::get(
				'users', 'activationEmailSubject', "Welcome! Please confirm your email address." 
			);
		}
		$fields2 = array_merge($fields, array('user' => $this, 'email' => $e));
		$e->sendMessage(
			$activation_email_subject, 
			$activation_email_view, 
			$fields2,
			array('html' => $html)
		);
		
		Pie::event('users/addEmail', compact('email_address'), 'after');
	}
	
	function setEmailAddress($email_address)
	{
		$e = new Users_Email();
		$e->address = $email_address;
		if (!$e->retrieve()) {
			throw new Pie_Exception_MissingRow(array(
				'table' => "an email",
				'criteria' => "address $email_address"
			), 'email_address');
		}
		if ($e->user_id != $this->id) {
			// We're going to tell them it's verified for someone else,
			// even though it may not have been verified yet.
			// In the future, might throw a more accurate exception.
			throw new Users_Exception_AlreadyVerified(array(
				'key' => $e->address,
				'user_id' => $e->user_id
			));
		}
		if ($e->state != 'unverified') {
			throw new Users_Exception_WrongState(array(
				'key' => $e->address,
				'state' => $e->state
			), 'email_address');
		}

		// Everything is okay. Assign it!
		$this->email_address = $email_address;
		$e->state = 'active';
		$e->save();
		Pie::event('users/setEmailAddress', compact('email_address'), 'after');
		return true;
	}
	
	/**
	 * Starts the process of adding a mobile to a saved user object.
	 * Also modifies and saves this user object back to the database.
	 * @param string $mobile_number
	 *  The mobile number to add.
	 * @param string $activation_mobile_view
	 *  The view to use for the body of the activation mobile to send.
	 * @param boolean $html
	 *  Defaults to true. Whether to send as HTML mobile.
	 * @param array $fields
	 *  An array of additional fields to pass to the mobile view.
	 * @return boolean
	 *  Returns true on success.
	 *  Returns false if this mobile number is already verified for this user.
	 * @throws Pie_Exception_WrongType
	 *  If the mobile number is in an invalid format, this is thrown.
	 * @throws Users_Exception_AlreadyVerified
	 *  If the mobile number already exists and has been verified for
	 *  another user, then this exception is thrown.
	 */
	function addMobile(
		$mobile_number,
		$activation_mobile_subject = null,
		$activation_mobile_view = null,
		$html = true,
		$fields = array())
	{
		// TODO: Implement Users_Mobile::sendMessage
		if (!Pie_Valid::mobile($mobile_number)) {
			throw new Pie_Exception_WrongValue(array(
				'field' => 'Mobile phone', 
				'range' => 'a valid number'
			), 'mobile_number');
		}
		Pie::event(
			'users/validate/mobile_number',
			array('mobile_number' => & $mobile_number)
		);
		$m = new Users_Mobile();
		$m->number = $mobile_number;
		if ($m->retrieve() and $m->state !== 'unverified') {
			if ($m->user_id === $this->id) {
				return false;
			}
			// Otherwise, say it's verified for another user,
			// even if it unsubscribed or was suspended.
			throw new Users_Exception_AlreadyVerified(array(
				'key' => $m->number,
				'user_id' => $m->user_id
			), 'mobile_number');
		}
		
		// If we are here, then the mobile record either
		// doesn't exist, or hasn't been verified yet.
		// In either event, update the record in the database,
		// and re-send the mobile.
		$minutes = Pie_Config::get('users', 'activationCodeExpires', 60*24*7);
		$m->state = 'unverified';
		$m->user_id = $this->id;
		$m->activation_code = Pie_Utils::unique(5);
		$m->activation_code_expires = new Db_Expression(
			"CURRENT_TIMESTAMP + INTERVAL $minutes MINUTE"
		);
		$m->auth_code = md5(microtime() + mt_rand());
		$m->save();
		
		if (!isset($activation_message_view)) {
			$activation_message_view = Pie_Config::get(
				'users', 'activationMessageView', 'users/message/activation.php'
			);
		}
		$fields2 = array_merge($fields, array('user' => $this, 'message' => $m));
		$m->sendMessage(
			$activation_mobile_view, 
			$fields2,
			array('html' => $html)
		);
		
		Pie::event('users/addMobile', compact('mobile_number'), 'after');
	}
	
	function setMobileNumber($mobile_number)
	{
		// TODO: implement Users_Mobile::sendMessage
		$m = new Users_Mobile();
		$m->number = $mobile_number;
		if (!$m->retrieve()) {
			throw new Pie_Exception_MissingRow(array(
				'table' => "a mobile phone",
				'criteria' => "number $mobile_number"
			), 'mobile_number');
		}
		if ($m->user_id != $this->id) {
			// We're going to tell them it's verified for someone else,
			// even though it may not have been verified yet.
			// In the future, might throw a more accurate exception.
			throw new Users_Exception_AlreadyVerified(array(
				'key' => $m->number,
				'user_id' => $m->user_id
			));
		}
		if ($m->state != 'unverified') {
			throw new Users_Exception_WrongState(array(
				'key' => $m->number,
				'state' => $m->state
			), 'mobile_number');
		}

		// Everything is okay. Assign it!
		$this->mobile_number = $mobile_number;
		$m->state = 'active';
		$m->save();
		Pie::event('users/setMobileNumber', compact('mobile_number'), 'after');
		return true;
	}
	
	/**
	 * Implements the __set_state method, so it can work with
	 * with var_export and be re-imported successfully.
	 */
	static function __set_state(array $array) {
		$result = new Users_User();
		foreach($array as $k => $v)
			$result->$k = $v;
		return $result;
	}
};
