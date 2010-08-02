<?php

/**
 * Db Library
 * by Gregory Magarshak
 * @package Db
 */


/**
 * Interface that an adapter must support
 * to implement the Db class.
 * @package Db
 */

interface iDb
{
	/**
	 * Constructor
	 *
	 * @param PDO $pdo 
	 *  The PDO connection.
	 */
	//function __construct (PDO $pdo, $conn_name);

	/**
	 * Forwards all other calls to the PDO object
	 *
	 * @param string $name 
	 *  The function name
	 * @param array $arguments 
	 *  The arguments
	 */
	//function __call ($name, array $arguments);

	/**
	 * Returns the name of the connection with which this Db object was created.
	 */
	function connectionName ();
	
	/**
	 * Returns the connection with which this Db object was created.
	 */
	function connection();
	
	/**
	 * Returns an associative array representing the dsn
	 */
	function dsnArray ();
	
	/**
	 * Returns the name of the database used
	 */
	function dbName();

	/**
	 * Creates a query to select fields from a table. Needs to be used with {@link Db_Query::from()}.
	 *
	 * @param string|array $fields 
	 *  The fields as strings, or array of alias=>field
	 * @param string|array $tables
	 *  The tables as strings, or array of alias=>table
	 * @return Db_Query
	 *  The resulting Db_Query object
	 */
	function select ($fields, $tables);

	/**
	 * Creates a query to insert a record into a table
	 *
	 * @param string $table_into
	 *  The name of the table to insert into
	 * @param array $fields
	 *  The fields as an array of column=>value pairs
	 * @return Db_Query
	 *  The resulting Db_Query object
	 */
	function insert ($table_into, array $fields = array());

	/**
	 * Inserts multiple records into a single table, preparing the statement only once,
	 * and executes all the queries.
	 *
	 * @param string $table_into
	 *  The name of the table to insert into
	 * @param array $records
	 *  The array of records to insert. 
	 *  (The field names for the prepared statement are taken from the first record.)
	 *  You cannot use Db_Expression objects here, because the function binds all parameters with PDO.
	 * @param int $chunk_size
	 *  The number of rows to insert at once. Default is 1.
	 *  If the database does not support this, $chunk_size will default to 1.
	 */
	function insertManyAndExecute ($table_into, array $records = array(), $chunk_size = 1);

	/**
	 * Creates a query to update records. Needs to be used with {@link Db_Query::set}
	 *
	 * @param string $table
	 *  The table to update
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function update ($table);

	/**
	 * Creates a query to delete records.
	 *
	 * @param string $table_from
	 *  The table to delete from
	 * @return Db_Query
	 */
	function delete ($table_from, $table_using = null);

	/**
	 * Creates a query from raw SQL
	 *
	 * @param string $sql
	 *  May contain more than one SQL statement
	 * @return Db_Query
	 */
	function rawQuery ($sql);

    /**
     * Sorts a table in chunks
     * @param string $table
     *  The name of the table in the database
     * @param string $pts_field
     *  The name of the field to rank by.
     * @param string $rank_field
     *  The rank field to update in all the rows
     * @param int $chunk_size
     *  The number of rows to process at a time. Default is 1000.
     *  This is so the queries don't tie up the database server for very long,
     *  letting it service website requests and other things.
     * @param int $rank_level2
     *  Since the ranking is done in chunks, the function must know
     *  which rows have not been processed yet. If this field is empty (default)
     *  then the function first sets the rank_field to 0 in all the rows.
     *  (That might be a time consuming operation.)
     *  Otherwise, if $rank is a nonzero integer, then the function alternates
     *  between the ranges
     *  0 to $rank_level2, and $rank_level2 to $rank_level2 * 2.
     *  That is, after it is finished, all the ratings will be in one of these
     *  two ranges.
     *  If not empty, this should be a very large number, like a billion.
     * @param string $order_by_clause
     *  The order clause to use when calculating ranks.
     *  Default is "ORDER BY $pts_field DESC"
     */
    function rank(
        $table,
        $pts_field, 
        $rank_field, 
        $chunk_size = 1000, 
        $rank_level2 = 0,
        $order_by_clause = null);
    
	/**
	 * Returns a timestamp from a DateTime string
	 *
	 * @param string $syntax
	 *  The format of the date string, see {@link date()} function.
	 * @param string $datetime
	 *  The DateTime string that comes from the db
	 * @return string
	 *  The timestamp
	 */
	function fromDateTime ($datetime);

	/**
	 * Returns a DateTime string to store in the database
	 *
	 * @param string $timestamp
	 *  The UNIX timestamp, e.g. from strtotime function
	 * @return string
	 */
	function toDateTime ($timestamp);
	
	/**
	 * Takes a SQL script and returns an array of queries.
	 * When DELIMITER is changed, respects that too.
	 * @param string $script
	 *  The text of the script
	 * @return array
	 *  An array of the SQL queries.
	 */
	 function scriptToQueries($script);
	
	/**
	 * Generates base classes of the models, and if they don't exist,
	 * skeleton code for the models themselves. 
	 * Use it only after you have made changes to the database schema.
	 * You shouldn't be using it on every request.
	 * @param string $conn_name
	 *  The name of a previously registered connection.
	 * @param string $directory
	 *  The directory in which to generate the files.
	 *  If the files already exist, they are not overwritten,
	 *  unless they are inside the "generated" subdirectory.
	 *  If the "generated" subdirectory does not exist, it is created.
	 * @param string $classname_prefix
	 *  The prefix to prepend to the generated class names.
	 *  If not specified, prefix becomes "Conn_Name_", 
	 *  where conn_name is the name of the connection.
	 * @throws Exception
	 *  If the $connection is not registered, or the $directory
	 *  does not exist, this function throws an exception.
	 */
	function generateModels (
		$directory, 
		$classname_prefix = null);
	
	/**
	 * Generates a base class for the model
	 * 
	 * @param string $table
	 *  The name of the table to generate the code for.
	 * @param string $directory
	 *  The path of the directory in which to place the model code.
	 * @param string& $class_name
	 *  If set, this is the class name that is used.
	 *  If an unset variable is passed, it is filled with the
	 *  class name that is ultimately chosen from the $classname_prefix
	 *  and $table_name.
	 * @return string
	 *  The generated code for the class.
	 */
	function codeForModelBaseClass ( 
		$table_name, 
		$directory,
		$classname_prefix = '',
		&$class_name = null,
		$prefix = null);
}

abstract class Db
{	
	/**
	 * The array of db objects that have been constructed
	 * @var array
	 */
 	public static $dbs;
	
	/**
	 * The database connections that have been added
	 * @var array
	 */
 	public static $connections;

	/**
	 * The array of all pdo objects that have been constructed
	 * @var array
	 */
	public static $pdo_array = array();

	/**
	 * Add a database connection with a name
	 * @param string $name
	 *  The name under which to store the connection details
	 * @param array $details
	 *  The connection details. Should include the keys:
	 *  'dsn', 'username', 'password', 'driver_options'
	 */
	static function addConnection ($name, $details)
	{
		if (class_exists('Pie_Config')) {
			Pie_Config::set('db', 'connections', $name, $details);
		} else {
			// Standalone, no Pie
			self::$connections[$name] = $details;
		}
	}

	/**
	 * Returns all the connections added thus far
	 * @return array
	 */
	static function getConnections ()
	{
		if (class_exists('Pie_Config')) {
			return Pie_Config::get('db', 'connections', array());
		}
		
		// Else standalone, no Pie
		return self::$connections;
	}

	/**
	 * Returns connection details for a connection
	 * @param $name
	 * @return array|false
	 */
	static function getConnection ($name)
	{
		if (class_exists('Pie_Config')) {
			return Pie_Config::get('db', 'connections', $name, null);
		}
			
		// Else standalone, no Pie
		if (! isset(self::$connections[$name]))
			return null;
		return self::$connections[$name];
	}

	/**
	 * Returns an associative array representing the dsn
	 * @param string $dsn_string
	 *  The dsn string passed to create the PDO object
	 * @return array
	 */
	static function parseDsnString($dsn_string)
	{
		$parts = explode(':', $dsn_string);
		$parts2 = explode(';', $parts[1]);
		$dsn_array = array();
		foreach ($parts2 as $part) {
			$parts3 = explode('=', $part);
			$dsn_array[$parts3[0]] = $parts3[1];
		}
		$dsn_array['dbms'] = strtolower($parts[0]);
		return $dsn_array;
	}

	/**
	 * This function uses Db to establish a connection
	 * with the information stored in the configuration.
	 * If the this Db object has already been made, 
	 * it returns this Db object.
	 * 
	 * Note: THIS FUNCTION NO LONGER CREATES A CONNECTION RIGHT OFF THE BAT.
	 * Instead, the real connection (PDO object) is only made when
	 * it is necessary (for example, when a query is executed).
	 *
	 * @param string $conn_name 
	 *  The name of the connection out of the connections added with Db::addConnection
	 */
	static function connect ($conn_name)
	{
		$conn_info = self::getConnection($conn_name);
		if (empty($conn_info))
			throw new Exception("Database connection \"$conn_name\" wasn't registered with Db.", -1);
		if (isset(self::$dbs[$conn_name]) and self::$dbs[$conn_name] instanceof iDb) {
			return self::$dbs[$conn_name];
		} else {
			$dsn_array = Db::parseDsnString($conn_info['dsn']);
			$class_name = 'Db_' . ucfirst($dsn_array['dbms']);
			if (!class_exists($class_name)) {
				$filename_to_include = dirname(__FILE__) 
				. DS . 'Db' 
				. DS . ucfirst($dsn_array['dbms']) . '.php';
				if (file_exists($filename_to_include)) {
					include ($filename_to_include);
				}
			}
			// Don't instantiate the PDO object until we need it
			$db_adapted = new $class_name($conn_name);
			Db::$dbs[$conn_name] = $db_adapted;
			return $db_adapted;
		}
	}

	/**
	 * Returns a timestamp from a Date string
	 * For backward compatibility. Works with MySQL and hopefully
	 * lots of other databases.
	 * 
	 * @param string $syntax
	 *  The format of the date string, see {@link date()} function.
	 * @param string $datetime
	 *  The DateTime string that comes from the db
	 * @return string
	 *  The timestamp
	 */
	static function fromDate ($datetime)
	{
		$year = substr($datetime, 0, 4);
		$month = substr($datetime, 5, 2);
		$day = substr($datetime, 8, 2);
		
		return mktime($month, $day, $year);
	}

	/**
	 * Returns a Date string to store in the database
	 * For backward compatibility. Works with MySQL and hopefully
	 * lots of other databases.
	 *
	 * @param string $timestamp
	 *  The UNIX timestamp, e.g. from strtotime function
	 * @return string
	 */
	static function toDate ($timestamp)
	{
		return date('Y-m-d', $timestamp);
	}
	
	/**
	 * Returns a timestamp from a DateTime string
	 * For backward compatibility. Works with MySQL and hopefully
	 * lots of other databases.
	 * 
	 * @param string $syntax
	 *  The format of the date string, see {@link date()} function.
	 * @param string $datetime
	 *  The DateTime string that comes from the db
	 * @return string
	 *  The timestamp
	 */
	static function fromDateTime ($datetime)
	{
		$year = substr($datetime, 0, 4);
		$month = substr($datetime, 5, 2);
		$day = substr($datetime, 8, 2);
		$hour = substr($datetime, 11, 2);
		$min = substr($datetime, 14, 2);
		$sec = substr($datetime, 17, 2);
		
		return mktime($hour, $min, $sec, $month, $day, $year);
	}

	/**
	 * Returns a DateTime string to store in the database
	 * For backward compatibility. Works with MySQL and hopefully
	 * lots of other databases.
	 *
	 * @param string $timestamp
	 *  The UNIX timestamp, e.g. from strtotime function
	 * @return string
	 */
	static function toDateTime ($timestamp)
	{
		return date('Y-m-d h:i:s', $timestamp);
	}
	
	/**
	 * Generates a class name given a table name
	 * @param string $table_name
	 * @return string
	 */
	static function generateTableClassName ($table_name)
	{
		$pieces = explode('_', $table_name);
		for ($i = 0, $count = count($pieces); $i < $count; ++ $i)
			$pieces[$i] = ucfirst($pieces[$i]);
		return implode($pieces, '');
	}

	/**
	 * Gets the key into the associative $pdo_array
	 * corresponding to some database credentials.
	 */
	static function pdo ($dsn, $username, $password, $driver_options)
	{
		$key = $dsn . $username . $password . serialize($driver_options);
		if (isset(self::$pdo_array[$key])) {
			return self::$pdo_array[$key];
		}
		self::$pdo_array[$key] = @new PDO($dsn, $username, $password, $driver_options);
		return self::$pdo_array[$key];
	}

	static function dump_table($rows)
	{
		$first_row = true;
		$keys = array();
		$lengths = array();
		foreach($rows as $row)
		{
			foreach($row as $key => $value)
			{
				if($first_row)
				{
					$keys[] = $key;
					$lengths[$key] = strlen($key);
				}
				$val_len = strlen((string) $value);
				if($val_len > $lengths[$key])
					$lengths[$key] = $val_len;
			}
			$first_row = false;
		}
		foreach($keys as $i => $key)
		{
			$key_len = strlen($key);
			if($key_len < $lengths[$key])
			{
				$keys[$i] .= str_repeat(' ', $lengths[$key] - $key_len);
			}
		}
		echo PHP_EOL;
		echo implode("\t", $keys);
		echo PHP_EOL;
		foreach($rows as $i => $row)
		{
			foreach($row as $key => $value)
			{
				$val_len = strlen((string) $value);
				if($val_len < $lengths[$key])
				{
					$row[$key] .= str_repeat(' ', $lengths[$key] - $val_len);
				}
			}
			echo implode("\t", $row);
			echo PHP_EOL;
		}
	}

}

/// { aggregate classes for production
/// Db/*.php
/// }
