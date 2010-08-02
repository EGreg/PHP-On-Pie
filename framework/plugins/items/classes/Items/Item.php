<?php

/**
 * Class representing item rows.
 * You can create an object of this class either to
 * access its non-static methods, or to actually
 * represent a item row in the items database.
 *
 * This description should be revised and expanded.
 *
 * @package items
 */
class Items_Item extends Base_Items_Item
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
	 * Completely resets the categories on the item to the ones passed.
	 * @param array $category_ids
	 *  An array of category ids.
	 */
	function setCategories($category_ids)
	{
		Items::db()->delete(Items_Tag::table())
			->where(array(
				'item_id' => $this->id,
				'attribute_id' => Items::ATTRIBUTE_CATEGORY
			))->execute();
		foreach ($category_ids as $cat_id) {
			if (empty($cat_id))
				continue;
			$tag = new Items_Tag();
			$tag->item_id = $this->id;
			$tag->attribute_id = Items::ATTRIBUTE_CATEGORY;
			$tag->category_id = $cat_id;
			$tag->weight = 1;
			$tag->save(true);
		}
	}
	
	/**
	 * Implements the __set_state method, so it can work with
	 * with var_export and be re-imported successfully.
	 */
	static function __set_state(array $array) {
		$result = new Items_Item();
		foreach($array as $k => $v)
			$result->$k = $v;
		return $result;
	}
};
