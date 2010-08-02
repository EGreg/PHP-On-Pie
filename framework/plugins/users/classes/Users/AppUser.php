<?php

/**
 * Class representing app_user rows.
 * You can create an object of this class either to
 * access its non-static methods, or to actually
 * represent a app_user row in the users database.
 *
 * This description should be revised and expanded.
 *
 * @package users
 */
class Users_AppUser extends Base_Users_AppUser
{
	/**
	 * The setUp() method is called the first time
	 * an object of this class is constructed.
	 */
	function setUp()
	{
		parent::setUp();
		// INSERT YOUR CODE HERE
		// e.g. $this->hasMany(...) and stuff like that.
	}
	
	/**
	 * Implements the __set_state method, so it can work with
	 * with var_export and be re-imported successfully.
	 */
	static function __set_state(array $array) {
		$result = new Users_AppUser();
		foreach($array as $k => $v)
			$result->$k = $v;
		return $result;
	}
};
