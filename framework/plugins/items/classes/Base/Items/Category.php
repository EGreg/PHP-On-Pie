<?php

/**
 * Autogenerated base class representing category rows
 * in the items database.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Items_Category.php file.
 *
 * @package items
 *
 * @property string $name
 * @property string $time_created
 * @property int $by_user_id
 * @property string $title
 * @property string $icon
 * @property mixed $state
 */
abstract class Base_Items_Category extends Db_Row
{
	function setUp()
	{
		$this->setDb(self::db());
		$this->setTable(self::table());
		$this->setPrimaryKey(
			array (
			  0 => 'name',
			)
		);
	}

	static function db()
	{
		return Db::connect('items');
	}

	static function table($with_db_name = true)
	{
		$conn = Db::getConnection('items');
		$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
		$table_name = $prefix . 'category';
		if (!$with_db_name)
			return $table_name;
		$db = Db::connect('items');
		return $db->dbName().'.'.$table_name;
	}
	
	static function connectionName()
	{
		return 'items';
	}

	/** @return Db_Query_Mysql */
	static function select($fields, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->select($fields, self::table().' '.$alias);
		$q->className = 'Items_Category';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function update($alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->update(self::table().' '.$alias);
		$q->className = 'Items_Category';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function delete($table_using = null, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->delete(self::table().' '.$alias, $table_using);
		$q->className = 'Items_Category';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insert($fields = array(), $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insert(self::table().' '.$alias, $fields);
		$q->className = 'Items_Category';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insertManyAndExecute($records = array(), $chunk_size = 1, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insertManyAndExecute(self::table().' '.$alias, $records, $chunk_size);
		$q->className = 'Items_Category';
		return $q;
	}
	
	function beforeSet_name($value)
	{
		if ($value instanceof Db_Expression) return array('name', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to items_category.name');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to items_category.name');
		return array('name', $value);			
	}

	function beforeSet_by_user_id($value)
	{
		if ($value instanceof Db_Expression) return array('by_user_id', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to items_category.by_user_id');
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception('Out-of-range value being assigned to items_category.by_user_id');
		return array('by_user_id', $value);			
	}

	function beforeSet_title($value)
	{
		if ($value instanceof Db_Expression) return array('title', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to items_category.title');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to items_category.title');
		return array('title', $value);			
	}

	function beforeSet_icon($value)
	{
		if ($value instanceof Db_Expression) return array('icon', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to items_category.icon');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to items_category.icon');
		return array('icon', $value);			
	}

	function beforeSet_state($value)
	{
		if ($value instanceof Db_Expression) return array('state', $value);
		if (!in_array($value, array('rejected','pending','searchable','published')))
			throw new Exception('Out-of-range value being assigned to items_category.state');
		return array('state', $value);			
	}

	function afterSet($name, $value)
	{
		if (!in_array($name, $this->fieldNames()))
			$this->notModified($name);
		return $value;			
	}

	function beforeSave($value)
	{
		if (!$this->retrieved) {
			foreach (array('by_user_id','state') as $name) {
				if (!isset($value[$name]))
					throw new Exception("The field items_category.$name needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
			}
		}
		return $value;			
	}

	function fieldNames($table_alias = null, $field_alias_prefix = null)
	{
		$field_names = array('name', 'time_created', 'by_user_id', 'title', 'icon', 'state');
		$result = $field_names;
		if (!empty($table_alias)) {
			$temp = array();
			foreach ($result as $field_name)
				$temp[] = $table_alias . '.' . $field_name;
			$result = $temp;
		} 
		if (!empty($field_alias_prefix)) {
			$temp = array();
			reset($field_names);
			foreach ($result as $field_name) {
				$temp[$field_alias_prefix . current($field_names)] = $field_name;
				next($field_names);
			}
			$result = $temp;
		}
		return $result;			
	}
};