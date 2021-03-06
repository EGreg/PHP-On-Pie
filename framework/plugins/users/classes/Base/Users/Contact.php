<?php

/**
 * Autogenerated base class representing contact rows
 * in the users database.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Users_Contact.php file.
 *
 * @package users
 *
 * @property int $user_id
 * @property string $label
 * @property int $contact_user_id
 * @property string $time_created
 * @property string $secret
 * @property string $contact_user_password_hash
 */
abstract class Base_Users_Contact extends Db_Row
{
	function setUp()
	{
		$this->setDb(self::db());
		$this->setTable(self::table());
		$this->setPrimaryKey(
			array (
			  0 => 'user_id',
			  1 => 'label',
			  2 => 'contact_user_id',
			)
		);
	}

	static function db()
	{
		return Db::connect('users');
	}

	static function table($with_db_name = true)
	{
		$conn = Db::getConnection('users');
		$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
		$table_name = $prefix . 'contact';
		if (!$with_db_name)
			return $table_name;
		$db = Db::connect('users');
		return $db->dbName().'.'.$table_name;
	}
	
	static function connectionName()
	{
		return 'users';
	}

	/** @return Db_Query_Mysql */
	static function select($fields, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->select($fields, self::table().' '.$alias);
		$q->className = 'Users_Contact';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function update($alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->update(self::table().' '.$alias);
		$q->className = 'Users_Contact';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function delete($table_using = null, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->delete(self::table().' '.$alias, $table_using);
		$q->className = 'Users_Contact';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insert($fields = array(), $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insert(self::table().' '.$alias, $fields);
		$q->className = 'Users_Contact';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insertManyAndExecute($records = array(), $chunk_size = 1, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insertManyAndExecute(self::table().' '.$alias, $records, $chunk_size);
		$q->className = 'Users_Contact';
		return $q;
	}
	
	function beforeSet_user_id($value)
	{
		if ($value instanceof Db_Expression) return array('user_id', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to users_contact.user_id');
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception('Out-of-range value being assigned to users_contact.user_id');
		return array('user_id', $value);			
	}

	function beforeSet_label($value)
	{
		if ($value instanceof Db_Expression) return array('label', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_contact.label');
		if (strlen($value) > 63)
			throw new Exception('Exceedingly long value being assigned to users_contact.label');
		return array('label', $value);			
	}

	function beforeSet_contact_user_id($value)
	{
		if ($value instanceof Db_Expression) return array('contact_user_id', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to users_contact.contact_user_id');
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception('Out-of-range value being assigned to users_contact.contact_user_id');
		return array('contact_user_id', $value);			
	}

	function beforeSet_secret($value)
	{
		if ($value instanceof Db_Expression) return array('secret', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_contact.secret');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to users_contact.secret');
		return array('secret', $value);			
	}

	function beforeSet_contact_user_password_hash($value)
	{
		if ($value instanceof Db_Expression) return array('contact_user_password_hash', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_contact.contact_user_password_hash');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to users_contact.contact_user_password_hash');
		return array('contact_user_password_hash', $value);			
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
			foreach (array('user_id','contact_user_id') as $name) {
				if (!isset($value[$name]))
					throw new Exception("The field users_contact.$name needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
			}
		}
		return $value;			
	}

	function fieldNames($table_alias = null, $field_alias_prefix = null)
	{
		$field_names = array('user_id', 'label', 'contact_user_id', 'time_created', 'secret', 'contact_user_password_hash');
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