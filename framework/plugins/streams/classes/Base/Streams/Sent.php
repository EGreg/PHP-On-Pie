<?php

/**
 * Autogenerated base class representing sent rows
 * in the streams database.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Streams_Sent.php file.
 *
 * @package streams
 *
 * @property int $publisher_id
 * @property string $stream_name
 * @property string $time_created
 * @property string $time_sent
 * @property string $thread_key
 * @property int $by_user_id
 * @property string $comment
 * @property string $instructions
 */
abstract class Base_Streams_Sent extends Db_Row
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
		return Db::connect('streams');
	}

	static function table($with_db_name = true)
	{
		$conn = Db::getConnection('streams');
		$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
		$table_name = $prefix . 'sent';
		if (!$with_db_name)
			return $table_name;
		$db = Db::connect('streams');
		return $db->dbName().'.'.$table_name;
	}
	
	static function connectionName()
	{
		return 'streams';
	}

	/** @return Db_Query_Mysql */
	static function select($fields, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->select($fields, self::table().' '.$alias);
		$q->className = 'Streams_Sent';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function update($alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->update(self::table().' '.$alias);
		$q->className = 'Streams_Sent';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function delete($table_using = null, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->delete(self::table().' '.$alias, $table_using);
		$q->className = 'Streams_Sent';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insert($fields = array(), $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insert(self::table().' '.$alias, $fields);
		$q->className = 'Streams_Sent';
		return $q;
	}

	/** @return Db_Query_Mysql */
	static function insertManyAndExecute($records = array(), $chunk_size = 1, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insertManyAndExecute(self::table().' '.$alias, $records, $chunk_size);
		$q->className = 'Streams_Sent';
		return $q;
	}
	
	function beforeSet_publisher_id($value)
	{
		if ($value instanceof Db_Expression) return array('publisher_id', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to streams_sent.publisher_id');
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception('Out-of-range value being assigned to streams_sent.publisher_id');
		return array('publisher_id', $value);			
	}

	function beforeSet_stream_name($value)
	{
		if ($value instanceof Db_Expression) return array('stream_name', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to streams_sent.stream_name');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to streams_sent.stream_name');
		return array('stream_name', $value);			
	}

	function beforeSet_time_sent($value)
	{
       if (!isset($value)) return array('time_sent', $value);
		if ($value instanceof Db_Expression) return array('time_sent', $value);
		$date = date_parse($value);
       if (!empty($date['errors']))
           throw new Exception("DateTime $value in incorrect format being assigned to streams_sent.time_sent");
       foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as $v)
           $$v = $date[$v];
       $value = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second);
		return array('time_sent', $value);			
	}

	function beforeSet_thread_key($value)
	{
		if ($value instanceof Db_Expression) return array('thread_key', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to streams_sent.thread_key');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to streams_sent.thread_key');
		return array('thread_key', $value);			
	}

	function beforeSet_by_user_id($value)
	{
		if ($value instanceof Db_Expression) return array('by_user_id', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to streams_sent.by_user_id');
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception('Out-of-range value being assigned to streams_sent.by_user_id');
		return array('by_user_id', $value);			
	}

	function beforeSet_comment($value)
	{
		if ($value instanceof Db_Expression) return array('comment', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to streams_sent.comment');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to streams_sent.comment');
		return array('comment', $value);			
	}

	function beforeSet_instructions($value)
	{
		if ($value instanceof Db_Expression) return array('instructions', $value);
		if (!is_string($value))
			throw new Exception('Must pass a string to streams_sent.instructions');
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to streams_sent.instructions');
		return array('instructions', $value);			
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
			foreach (array('publisher_id','by_user_id') as $name) {
				if (!isset($value[$name]))
					throw new Exception("The field streams_sent.$name needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
			}
		}
		return $value;			
	}

	function fieldNames($table_alias = null, $field_alias_prefix = null)
	{
		$field_names = array('publisher_id', 'stream_name', 'time_created', 'time_sent', 'thread_key', 'by_user_id', 'comment', 'instructions');
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