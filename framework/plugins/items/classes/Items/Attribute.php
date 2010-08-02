<?php

/**
 * Class representing attribute rows.
 * You can create an object of this class either to
 * access its non-static methods, or to actually
 * represent a attribute row in the items database.
 *
 * This description should be revised and expanded.
 *
 * @package items
 */
class Items_Attribute extends Base_Items_Attribute
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
		$result = new Items_Attribute();
		foreach($array as $k => $v)
			$result->$k = $v;
		return $result;
	}
};
