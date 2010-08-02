<?php

/**
 * Autogenerated base class representing zipcode rows
 * in the places database.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Places_Zipcode.php file.
 *
 * @package places
 *
 * @property string $country_code
 * @property string $zipcode
 * @property string $place_name
 * @property string $state_name
 * @property string $state
 * @property string $region_name
 * @property string $region
 * @property string $community
 * @property mixed $latitude
 * @property mixed $longitude
 * @property int $accuracy
 */
abstract class Base_Places_Zipcode extends Db_Row
{
	function setUp()
	{
		$this->setDb(self::db());
		$this->setTable(self::table());
		$this->setPrimaryKey(
			array (
			)
		);
	}

	static function db()
	{
		return Db::connect('places');
	}

	static function table($with_db_name = true)
	{
		$conn = Db::getConnection('places');
		$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
		$table_name = $prefix . 'zipcode';
		if (!$with_db_name)
			return $table_name;
		$db = Db::connect('places');
		return $db->dbName().'.'.$table_name;
	}
	
	static function connectionName()
	{
		return 'places';
	}

	/** @return Db_Query_Mysql */
	static function select($fields, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->select($fields, self::table().' '.$alias);
		$q->className = 'Places_Zipcode';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function update($alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->update(self::table().' '.$alias);
		$q->className = 'Places_Zipcode';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function delete($table_using = null, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->delete(self::table().' '.$alias, $table_using);
		$q->className = 'Places_Zipcode';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insert($fields = array(), $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insert(self::table().' '.$alias, $fields);
		$q->className = 'Places_Zipcode';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insertManyAndExecute($records = array(), $chunk_size = 1, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insertManyAndExecute(self::table().' '.$alias, $records, $chunk_size);
		$q->className = 'Places_Zipcode';
		return $q;
	}
	
	function beforeSet_country_code($value)
	{
		if ($value instanceof Db_Expression) return array('country_code', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.country_code');
		if (strlen($value) > 2)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.country_code');
		return array('country_code', $value);			
	}

	function beforeSet_zipcode($value)
	{
		if ($value instanceof Db_Expression) return array('zipcode', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.zipcode');
		if (strlen($value) > 10)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.zipcode');
		return array('zipcode', $value);			
	}

	function beforeSet_place_name($value)
	{
		if ($value instanceof Db_Expression) return array('place_name', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.place_name');
		if (strlen($value) > 180)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.place_name');
		return array('place_name', $value);			
	}

	function beforeSet_state_name($value)
	{
		if ($value instanceof Db_Expression) return array('state_name', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.state_name');
		if (strlen($value) > 100)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.state_name');
		return array('state_name', $value);			
	}

	function beforeSet_state($value)
	{
		if ($value instanceof Db_Expression) return array('state', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.state');
		if (strlen($value) > 20)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.state');
		return array('state', $value);			
	}

	function beforeSet_region_name($value)
	{
		if ($value instanceof Db_Expression) return array('region_name', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.region_name');
		if (strlen($value) > 100)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.region_name');
		return array('region_name', $value);			
	}

	function beforeSet_region($value)
	{
		if ($value instanceof Db_Expression) return array('region', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.region');
		if (strlen($value) > 20)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.region');
		return array('region', $value);			
	}

	function beforeSet_community($value)
	{
		if ($value instanceof Db_Expression) return array('community', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to places_zipcode.community');
		if (strlen($value) > 100)
			throw new Exception('Exceedingly long value being assigned to places_zipcode.community');
		return array('community', $value);			
	}

	function beforeSet_accuracy($value)
	{
		if ($value instanceof Db_Expression) return array('accuracy', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to places_zipcode.accuracy');
		if ($value < -2147483648 or $value > 2147483647)
			throw new Exception('Out-of-range value being assigned to places_zipcode.accuracy');
		return array('accuracy', $value);			
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
			foreach (array('accuracy') as $name) {
				if (!isset($value[$name]))
					throw new Exception("The field places_zipcode.$name needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
			}
		}
		return $value;			
	}

	function fieldNames($table_alias = null, $field_alias_prefix = null)
	{
		$field_names = array('country_code', 'zipcode', 'place_name', 'state_name', 'state', 'region_name', 'region', 'community', 'latitude', 'longitude', 'accuracy');
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