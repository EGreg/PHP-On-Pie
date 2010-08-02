<?php

/**
 * Autogenerated base class representing user rows
 * in the users database.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Users_User.php file.
 *
 * @package users
 *
 * @property int $id
 * @property string $time_created
 * @property string $time_updated
 * @property string $session_key
 * @property string $iphone_key
 * @property int $fb_uid
 * @property string $password_hash
 * @property string $login_token
 * @property string $login_token_expires
 * @property string $email_address
 * @property string $mobile_number
 * @property string $username
 * @property string $icon
 */
abstract class Base_Users_User extends Db_Row
{
	function setUp()
	{
		$this->setDb(self::db());
		$this->setTable(self::table());
		$this->setPrimaryKey(
			array (
			  0 => 'id',
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
		$table_name = $prefix . 'user';
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
		$q->className = 'Users_User';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function update($alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->update(self::table().' '.$alias);
		$q->className = 'Users_User';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function delete($table_using = null, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->delete(self::table().' '.$alias, $table_using);
		$q->className = 'Users_User';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insert($fields = array(), $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insert(self::table().' '.$alias, $fields);
		$q->className = 'Users_User';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insertManyAndExecute($records = array(), $chunk_size = 1, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insertManyAndExecute(self::table().' '.$alias, $records, $chunk_size);
		$q->className = 'Users_User';
		return $q;
	}
	
	function beforeSet_id($value)
	{
		if ($value instanceof Db_Expression) return array('id', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to users_user.id');
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception('Out-of-range value being assigned to users_user.id');
		return array('id', $value);			
	}

	function beforeSet_time_updated($value)
	{
       if ($value instanceof Db_Expression) return array('time_updated', $value);
		$date = date_parse($value);
       if (!empty($date['errors']))
           throw new Exception("DateTime $value in incorrect format being assigned to users_user.time_updated");
       foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as $v)
           $$v = $date[$v];
       $value = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second);
		return array('time_updated', $value);			
	}

	function beforeSet_session_key($value)
	{
		if ($value instanceof Db_Expression) return array('session_key', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.session_key');
		if (strlen($value) > 32)
			throw new Exception('Exceedingly long value being assigned to users_user.session_key');
		return array('session_key', $value);			
	}

	function beforeSet_iphone_key($value)
	{
		if ($value instanceof Db_Expression) return array('iphone_key', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.iphone_key');
		if (strlen($value) > 32)
			throw new Exception('Exceedingly long value being assigned to users_user.iphone_key');
		return array('iphone_key', $value);			
	}

	function beforeSet_fb_uid($value)
	{
		if ($value instanceof Db_Expression) return array('fb_uid', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to users_user.fb_uid');
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception('Out-of-range value being assigned to users_user.fb_uid');
		return array('fb_uid', $value);			
	}

	function beforeSet_password_hash($value)
	{
		if (!isset($value)) return array('password_hash', $value);
		if ($value instanceof Db_Expression) return array('password_hash', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.password_hash');
		if (strlen($value) > 32)
			throw new Exception('Exceedingly long value being assigned to users_user.password_hash');
		return array('password_hash', $value);			
	}

	function beforeSet_login_token($value)
	{
		if (!isset($value)) return array('login_token', $value);
		if ($value instanceof Db_Expression) return array('login_token', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.login_token');
		if (strlen($value) > 32)
			throw new Exception('Exceedingly long value being assigned to users_user.login_token');
		return array('login_token', $value);			
	}

	function beforeSet_login_token_expires($value)
	{
       if (!isset($value)) return array('login_token_expires', $value);
		if ($value instanceof Db_Expression) return array('login_token_expires', $value);
		$date = date_parse($value);
       if (!empty($date['errors']))
           throw new Exception("DateTime $value in incorrect format being assigned to users_user.login_token_expires");
       foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as $v)
           $$v = $date[$v];
       $value = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second);
		return array('login_token_expires', $value);			
	}

	function beforeSet_email_address($value)
	{
		if (!isset($value)) return array('email_address', $value);
		if ($value instanceof Db_Expression) return array('email_address', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.email_address');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to users_user.email_address');
		return array('email_address', $value);			
	}

	function beforeSet_mobile_number($value)
	{
		if (!isset($value)) return array('mobile_number', $value);
		if ($value instanceof Db_Expression) return array('mobile_number', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.mobile_number');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to users_user.mobile_number');
		return array('mobile_number', $value);			
	}

	function beforeSet_username($value)
	{
		if ($value instanceof Db_Expression) return array('username', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.username');
		if (strlen($value) > 63)
			throw new Exception('Exceedingly long value being assigned to users_user.username');
		return array('username', $value);			
	}

	function beforeSet_icon($value)
	{
		if ($value instanceof Db_Expression) return array('icon', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to users_user.icon');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to users_user.icon');
		return array('icon', $value);			
	}

	function afterSet($name, $value)
	{
		if (!in_array($name, $this->fieldNames()))
			$this->notModified($name);
		return $value;			
	}

	function beforeSave($value)
	{
		//if ($this->retrieved and !isset($value['time_updated']))
		// convention: we'll have time_updated = time_created if just created.
			$value['time_updated'] = new Db_Expression('CURRENT_TIMESTAMP');			
		return $value;			
	}

	function fieldNames($table_alias = null, $field_alias_prefix = null)
	{
		$field_names = array('id', 'time_created', 'time_updated', 'session_key', 'iphone_key', 'fb_uid', 'password_hash', 'login_token', 'login_token_expires', 'email_address', 'mobile_number', 'username', 'icon');
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