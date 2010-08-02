<?php

/**
 * This class lets you create and use PDO database connections.
 * @method lastInsertId()
 * @package Db
 */

class Db_Mysql implements iDb
{
	/**
	 * The PDO connection that this object uses
	 * @var $pdo PDO
	 */
	public $pdo;
	
	/**
	 * The name of the connection
	 * @var conn_name
	 */
	protected $conn_name;

	/**
	 * Constructor
	 *
	 * @param string $conn_name
	 *  The name of the connection out of the connections added with Db::addConnection 
	 *  This is required for actually connecting to the database.
	 * @param PDO $pdo 
	 *  Optional. Existing PDO connection. Only accepts connections to MySQL.
	 */
	function __construct ($conn_name, PDO $pdo = null)
	{
		$this->conn_name = $conn_name;
		if ($pdo) {
			// The following statement may throw an exception, which is fine.
			$driver_name = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
			if (strtolower($driver_name) != 'mysql')
				throw new Exception("The PDO object is not for mysql", -1);

			$this->pdo = $pdo;
		}
	}

	/**
	 * Actually makes a connection to the database (by creating a PDO instance)
	 * @param array $modifications
	 *  The modifications to the connection info, if any.
	 *  Can contain the keys "dsn", "username", "password", "driver_options"
	 *  They are used in constructing the PDO object.
	 */
	function pdoConnect($modifications = array())
	{
		if ($this->pdo)
			return;
			
		if (!isset($modifications)) {
			$modifications = array();
		}
		
		$conn_name = $this->conn_name;
		$conn_info = Db::getConnection($conn_name);
		if (empty($conn_info))
			throw new Exception("Database connection \"$conn_name\" wasn't registered with Db.", -1);
			
		if (! isset($conn_info['driver_options']))
			$conn_info['driver_options'] = null;
		$dsn = isset($modifications['dsn']) ? $modifications['dsn'] : $conn_info['dsn'];
		$username = isset($modifications['username']) ? $modifications['username'] : $conn_info['username'];
		$password = isset($modifications['password']) ? $modifications['password'] : $conn_info['password'];
		$driver_options = isset($modifications['driver_options']) ? $modifications['driver_options'] : $conn_info['driver_options'];
		$this->pdo = Db::pdo($dsn, $username, $password, $driver_options);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Forwards all other calls to the PDO object
	 *
	 * @param string $name 
	 *  The function name
	 * @param array $arguments 
	 *  The arguments
	 */
	function __call ($name, array $arguments)
	{
		$this->pdoConnect();
		if (!is_callable(array($this->pdo, $name))) {
			throw new Exception("Neither Db_Mysql nor PDO supports the $name function");
		}
		return call_user_func_array(array($this->pdo, $name), $arguments);
	}

	/**
	 * Returns the name of the connection with which this Db object was created.
	 */
	function connectionName ()
	{
		if (isset($this->conn_name)) {
			return $this->conn_name;
		} else {
			return null;
		}
	}
	
	/**
	 * Returns the connection info with which this Db object was created.
	 */
	function connection()
	{
		if (isset($this->conn_name)) {
			return Db::getConnection($this->conn_name);
		} else {
			return null;
		}
	}
	
	/**
	 * Returns an associative array representing the dsn
	 */
	function dsnArray()
	{
		$conn_info = Db::getConnection($this->conn_name);
		if (empty($conn_info['dsn']))
			throw new Exception('No dsn string found for the connection ' 
			. $this->conn_name);
		return Db::parseDsnString($conn_info['dsn']);
	}
	
	/**
	 * Returns the name of the database used
	 */
	function dbName()
	{
		$dsn = $this->dsnArray();
		if (empty($dsn))
			return null;
		return $dsn['dbname'];
	}

	/**
	 * Creates a query to select fields from a table. Needs to be used with {@link Db_Query::from()}.
	 *
	 * @param string|array $fields 
	 *  The fields as strings, or array of alias=>field
	 * @param string|array $tables
	 *  The tables as strings, or array of alias=>table
	 * @return Db_Query_Mysql
	 *  The resulting Db_Query object
	 */
	function select ($fields, $tables)
	{
		if (empty($fields))
			throw new Exception("Fields not specified in call to 'select'.");
		if (empty($tables))
			throw new Exception("Tables not specified in call to 'select'.");
		$query = new Db_Query_Mysql($this, Db_Query::TYPE_SELECT);
		return $query->select($fields, $tables);
	}

	/**
	 * Creates a query to insert a record into a table
	 *
	 * @param string $table_into
	 *  The name of the table to insert into
	 * @param array $fields
	 *  The fields as an array of column=>value pairs
	 * @return Db_Query_Mysql
	 *  The resulting Db_Query_Mysql object
	 */
	function insert ($table_into, array $fields = array())
	{	
		if (empty($table_into))
			throw new Exception("Table not specified in call to 'insert'.");
		
		// $fields might be an empty array,
		// but the insert will still be attempted.
		
		$columns_list = array();
		$values_list = array();
		foreach ($fields as $column => $value) {
			$columns_list[] = "$column";
			if ($value instanceof Db_Expression)
				$values_list[] = "$value"; else
				$values_list[] = ":$column";
		}
		$columns_string = implode(', ', $columns_list);
		$values_string = implode(', ', $values_list);
		
		$clauses = array(
			'INTO' => "$table_into ($columns_string)", 'VALUES' => $values_string
		);
		
		return new Db_Query_Mysql($this, Db_Query::TYPE_INSERT, $clauses, $fields);
	}

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
	 *  The number of rows to insert at a time. Defaults to 1.
	 */
	function insertManyAndExecute ($table_into, array $records = array(), $chunk_size = 1)
	{		
		if (empty($table_into))
			throw new Exception("Table not specified in call to 'insertManyAndExecute'.");
		
		if (count($records) == 0)
			return false;
			
		if ($chunk_size < 1)
			return false;
			
		// Get the columns list
		foreach ($records[0] as $column => $value)
			$columns_list[] = "$column";
		$columns_string = implode(', ', $columns_list);
		
		$into = "$table_into ($columns_string)";
		$index = 1;
		$first_chunk = true;
		$to_bind = array();
		$record_count = count($records);
		
		// Execute all the queries using this prepared statement
		$row_of_chunk = 1;
		foreach ($records as $record) {
			if ($first_chunk) {
				// Prepare statement from first query
				$values_list = array();
				foreach ($record as $column => $value) {
					if ($value instanceof Db_Expression) {
						$values_list[] = "$value";
					} else {
						$values_list[] = ":$column" . $row_of_chunk;
					}
				}
				$values_string = implode(', ', $values_list);
				if ($index == 1) {
					$q = "INSERT INTO $into VALUES ($values_string) ";
				} else {
					$q .= "\n\t ($values_string) ";
				}
			}
			
			foreach ($record as $column => $value)
				$to_bind[$column . $row_of_chunk] = $value;
				
			++$row_of_chunk;
			if ($row_of_chunk % $chunk_size == 1
			or $index == $record_count) {
				if ($first_chunk) {
					$q .= ';';
					$this->pdoConnect();
					$stmt = $this->pdo->prepare($q);
					$first_chunk = false;
				}
				foreach ($to_bind as $key => $value) {
					$stmt->bindValue($key, $value);
				}
				$stmt->execute();
				$to_bind = array();
				$row_of_chunk = 1;
			}
			++$index;
		}
	}

	/**
	 * Creates a query to update records. Needs to be used with {@link Db_Query::set}
	 *
	 * @param string $table
	 *  The table to update
	 * @return Db_Query_Mysql 
	 *  The resulting Db_Query object
	 */
	function update ($table)
	{		
		if (empty($table))
			throw new Exception("Table not specified in call to 'update'.");
		
		$clauses = array('UPDATE' => "$table");
		return new Db_Query_Mysql($this, Db_Query::TYPE_UPDATE, $clauses);
	}

	/**
	 * Creates a query to delete records.
	 *
	 * @param string $table_from
	 *  The table to delete from
	 * @param string $table_using
	 *  If set, adds a USING clause with this table.
	 *  You can then use ->join() with the resulting Db_Query.
	 * @return Db_Query_Mysql
	 */
	function delete ($table_from, $table_using = null)
	{	
		if (empty($table_from))
			throw new Exception("Table not specified in call to 'delete'.");
		
		if (isset($table_using))
			$clauses = array('FROM' => "$table_from USING $table_using");
		else
			$clauses = array('FROM' => "$table_from");
		return new Db_Query_Mysql($this, Db_Query::TYPE_DELETE, $clauses);
	}

	/**
	 * Creates a query from raw SQL
	 *
	 * @param string $sql
	 *  May contain more than one SQL statement
	 * @return Db_Query_Mysql
	 */
	function rawQuery ($sql)
	{
		$clauses = array('RAW' => $sql);
		return new Db_Query_Mysql($this, Db_Query::TYPE_RAW, $clauses);
	}

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
     *  then the function sets the rank_field to 0 in all the rows, before
     *  starting the ranking process.
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
        $order_by_clause = null)
    {	
        if (!isset($order_by_clause))
            $order_by_clause = "ORDER BY $pts_field DESC";
            
        if (empty($rank_level2)) {
            $this->update($table)
                ->set(array($rank_field => 0))
                ->execute();
            $rank_base = 0;
            $condition = "$rank_field = 0 OR $rank_field IS NULL";
        } else {
            $rows = $this->select($pts_field, $table)
                ->where("$rank_field < $rank_level2")
                ->limit(1)
                ->execute()->fetchAll();
            if (!empty($rows)) {
        		// There are no ranks above $rank_level2. Create ranks on level 2.
        		$rank_base = $rank_level2;
        		$condition = "$rank_field < $rank_level2";
        	} else {
        		// The ranks are all above $rank_level2. Create ranks on level 1.
        		$rank_base = 0;
        		$condition = "$rank_field >= $rank_level2";
        	}
        }
        
        // Count all the rows
    	$row = $this->rawQuery("SELECT COUNT(1) as _count FROM $table")
            ->execute()->fetch(PDO::FETCH_ASSOC);
    	$count = $row['_count'];
    	
        // Here comes the magic:
        $offset = 0;
		$this->rawQuery("set @rank = $offset")->execute();
        do {
    		$this->rawQuery("
    			UPDATE $table 
    			SET $rank_field = $rank_base + (@rank := @rank + 1)
    			WHERE $condition
    			$order_by_clause
    			LIMIT $chunk_size
    		")->execute();
			$offset += $chunk_size;
        } while ($count-$offset > 0);
    }

	/**
	 * Returns a timestamp from a Date string
	 *
	 * @param string $syntax
	 *  The format of the date string, see {@link date()} function.
	 * @param string $datetime
	 *  The Date string that comes from the db
	 * @return int
	 *  The timestamp
	 */
	function fromDate ($date)
	{
		$year = substr($date, 0, 4);
		$month = substr($date, 5, 2);
		$day = substr($date, 8, 2);

		return mktime(0, 0, 0, $month, $day, $year);
	}
    
	/**
	 * Returns a timestamp from a DateTime string
	 *
	 * @param string $syntax
	 *  The format of the date string, see {@link date()} function.
	 * @param string $datetime
	 *  The DateTime string that comes from the db
	 * @return int
	 *  The timestamp
	 */
	function fromDateTime ($datetime)
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
	 * Returns a Date string to store in the database
	 *
	 * @param string $timestamp
	 *  The UNIX timestamp, e.g. from a strtotime function
	 * @return string
	 */
	function toDate ($timestamp)
	{
		return date('Y-m-d', $timestamp);
	}

	/**
	 * Returns a DateTime string to store in the database
	 *
	 * @param string $timestamp
	 *  The UNIX timestamp, e.g. from a strtotime function
	 * @return string
	 */
	function toDateTime ($timestamp)
	{
		return date('Y-m-d h:i:s', $timestamp);
	}
	
	/**
	 * Takes a MySQL script and returns an array of queries.
	 * When DELIMITER is changed, respects that too.
	 * @param string $script
	 *  The text of the script
	 * @return array
	 *  An array of the SQL queries.
	 */
	function scriptToQueries($script)
	{
		$this->pdoConnect();
		$version_string = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
		$version_parts = explode('.', $version_string);
		sprintf("%1d%02d%02d", $version_parts[0], $version_parts[1], $version_parts[2]);
		
		$script_stripped = $script;
		return $this->scriptToQueries_internal($script_stripped);
	}
	
	protected function scriptToQueries_internal($script)
	{
		$queries = array();
	
		$this->pdoConnect();
		$version_string = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
		$version_parts = explode('.', $version_string);
		$version = sprintf("%1d%02d%02d", $version_parts[0], $version_parts[1], $version_parts[2]);
		
		//$mode_n = 0;  // normal 
		$mode_c = 1;  // comments
		$mode_sq = 2; // single quotes
		$mode_dq = 3; // double quotes
		$mode_bt = 4; // backticks
		$mode_lc = 5; // line comment (hash or double-dash)
		$mode_ds = 6; // delimiter statement
		
		$cur_pos = 0;
		$d = ';'; // delimiter
		$d_len = strlen($d);
		$query_start_pos = 0;
		
		$del_start_pos_array = array();
		$del_end_pos_array = array();
		
		while (1) {
			
			$c_pos = strpos($script, "/*", $cur_pos);
			$sq_pos = strpos($script, "'", $cur_pos);
			$dq_pos = strpos($script, "\"", $cur_pos);
			$bt_pos = strpos($script, "`", $cur_pos);
			$c2_pos = strpos($script, "--", $cur_pos);
			$c3_pos = strpos($script, "#", $cur_pos);
			$ds_pos = stripos($script, "\nDELIMITER ", $cur_pos);

			$next_pos = false;
			if ($c_pos !== false) {
				$next_mode = $mode_c;
				$next_pos = $c_pos;
				$next_end_str = "*/";
				$next_end_str_len = 2;
			}
			if ($sq_pos !== false and ($next_pos === false or $sq_pos < $next_pos)) {
				$next_mode = $mode_sq;
				$next_pos = $sq_pos;
				$next_end_str = "'";
				$next_end_str_len = 1;
			}
			if ($dq_pos !== false and ($next_pos === false or $dq_pos < $next_pos)) {
				$next_mode = $mode_dq;
				$next_pos = $dq_pos;
				$next_end_str = "\"";
				$next_end_str_len = 1;
			}
			if ($bt_pos !== false and ($next_pos === false or $bt_pos < $next_pos)) {
				$next_mode = $mode_bt;
				$next_pos = $bt_pos;
				$next_end_str = "`";
				$next_end_str_len = 1;
			}
			if ($c2_pos !== false and ($next_pos === false or $c2_pos < $next_pos)
			and ($script[$c2_pos+2] == " " or $script[$c2_pos+2] == "\t")) {
				$next_mode = $mode_lc;
				$next_pos = $c2_pos;
				$next_end_str = "\n";
				$next_end_str_len = 1;
			}
			if ($c3_pos !== false and ($next_pos === false or $c3_pos < $next_pos)) {
				$next_mode = $mode_lc;
				$next_pos = $c3_pos;
				$next_end_str = "\n";
				$next_end_str_len = 1;
			}
			if ($ds_pos !== false and ($next_pos === false or $ds_pos < $next_pos)) {
				$next_mode = $mode_ds;
				$next_pos = $ds_pos;
				$next_end_str = "\n";
				$next_end_str_len = 1;
			}
			
			// If at this point, $next_pos === false, then
			// we are in the final stretch.
			// Until the end of the string, we have normal mode.
			
			// Right now, we are in normal mode.
			$d_pos = strpos($script, $d, $cur_pos);
			while ($d_pos !== false and ($next_pos === false or $d_pos < $next_pos)) {
				$query = substr($script, $query_start_pos, $d_pos - $query_start_pos);
	
				// remove parts of the query string based on the "del_" arrays
				$del_pos_count = count($del_start_pos_array);
				if ($del_pos_count == 0) {
					$query2 = $query;
				} else {
					$query2 = substr($query, 0, $del_start_pos_array[0] - $query_start_pos);
					for ($i=1; $i < $del_pos_count; ++$i) {
						$query2 .= substr($query, $del_end_pos_array[$i-1]  - $query_start_pos, 
							$del_start_pos_array[$i] - $del_end_pos_array[$i-1]);
					}
					$query2 .= substr($query, 
						$del_end_pos_array[$del_pos_count - 1] - $query_start_pos);
				}
	
				$del_start_pos_array = array(); // reset these arrays
				$del_end_pos_array = array(); // reset these arrays
	
				$query_start_pos = $d_pos + $d_len;
				$cur_pos = $query_start_pos;
	
				$query2 = trim($query2);
				if ($query2)
					$queries[] = $query2; // <----- here is where we add to the main array
				
				$d_pos = strpos($script, $d, $cur_pos);
			};
			
			if ($next_pos === false) {
				// Add the last query and get out of here:
				$query = substr($script, $query_start_pos);

				// remove parts of the query string based on the "del_" arrays
				$del_pos_count = count($del_start_pos_array);
				if ($del_pos_count == 0) {
					$query2 = $query;
				} else {
					$query2 = substr($query, 0, $del_start_pos_array[0] - $query_start_pos);
					for ($i=1; $i < $del_pos_count; ++$i) {
						$query2 .= substr($query, $del_end_pos_array[$i-1]  - $query_start_pos, 
							$del_start_pos_array[$i] - $del_end_pos_array[$i-1]);
					}
					if ($del_end_pos_array[$del_pos_count - 1] !== false) {
						$query2 .= substr($query, 
							$del_end_pos_array[$del_pos_count - 1] - $query_start_pos);
					}
				}
				
				$query2 = trim($query2);
				if ($query2)
					$queries[] = $query2;
				break;
			}
			
			if ($next_mode == $mode_c) {
				// We are inside a comment
				$end_pos = strpos($script, $next_end_str, $next_pos + 1);
				if ($end_pos === false) {
					throw new Exception("Unterminated comment -- missing terminating */ characters.");
				}
				
				$version_comment = false;
				if ($script[$next_pos + 2] == '!') {
					$ver = substr($script, $next_pos + 3, 5);
					if ($version >= $ver) {
						// we are in a version comment
						$version_comment = true;
					}
				}
				
				// Add to list of areas to ignore
				if ($version_comment) {
					$del_start_pos_array[] = $next_pos;
					$del_end_pos_array[] = $next_pos + 3 + 5;
					$del_start_pos_array[] = $end_pos;
					$del_end_pos_array[] = $end_pos + $next_end_str_len;
				} else {
					$del_start_pos_array[] = $next_pos;
					$del_end_pos_array[] = $end_pos + $next_end_str_len;
				}
			} else if ($next_mode == $mode_lc) {
				// We are inside a line comment
				$end_pos = strpos($script, $next_end_str, $next_pos + 1);
				$del_start_pos_array[] = $next_pos;
				if ($end_pos !== false) {
					$del_end_pos_array[] = $end_pos + $next_end_str_len;
				} else {
					$del_end_pos_array[] = false;
				}
			} else if ($next_mode == $mode_ds) {
				// We are inside a DELIMITER statement
				$start_pos = $next_pos;
				$end_pos = strpos($script, $next_end_str, $next_pos + 11);
				$del_start_pos_array[] = $next_pos;
				if ($end_pos !== false) {
					$del_end_pos_array[] = $end_pos + $next_end_str_len;
				} else {
					// this is the last statement in the script, it seems.
					// Might look funny, like:
					// DELIMITER aa sfghjkhsgkjlfhdsgjkfdglhdfsgkjfhgjdlk
					$del_end_pos_array[] = false;
				}
				// set a new delimiter!
				$try_d = trim(substr($script, $ds_pos + 11, $end_pos - ($ds_pos + 11)));
				if (!empty($try_d)) {
					$d = $try_d;
					$d_len = strlen($d);
				} // otherwise malformed delimiter statement or end of file
			} else {
				// We are inside a string
				$start_pos = $next_pos;
				$try_end_pos = $next_pos;
				do {
					$end_pos = false;
					$try_end_pos = strpos($script, $next_end_str, $try_end_pos + 1);
					if ($try_end_pos === false) {
						throw new Exception("Unterminated string -- missing terminating $next_end_str character.");
					}
					if ($script[$try_end_pos+1] == $next_end_str) {
						// this is of the type '' or "", etc. which should be like \' or \"
						++$try_end_pos;
						continue;
					}
					$bs_count = 0;
					for ($i = $try_end_pos - 1; $i > $next_pos; --$i) {
						if ($script[$i] == "\\") {
							++$bs_count;
						} else {
							break;
						}
					}
					if ($bs_count % 2 == 0) {
						$end_pos = $try_end_pos;
					}
				} while ($end_pos === false);
				// If we are here, we have found the end of the string,
				// and are back in normal mode.
			}
	
			// We have exited the previous mode and set end_pos.
			if ($end_pos === false)
				break;
			$cur_pos = $end_pos + $next_end_str_len;
		}
		
		foreach ($queries as $i => $query) {
			if ($query === false) {
				unset($queries[$i]);
			}
		}

		return $queries;
	}
	
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
	 *  unless they are inside the "Base" subdirectory.
	 *  If the "Base" subdirectory does not exist, it is created.
	 * @param string $classname_prefix
	 *  The prefix to prepend to the Base class names.
	 *  If not specified, prefix becomes "Conn_Name_", 
	 *  where conn_name is the name of the connection.
	 * @throws Exception
	 *  If the $connection is not registered, or the $directory
	 *  does not exist, this function throws an exception.
	 */
	function generateModels (
		$directory, 
		$classname_prefix = null)
	{
		if (!file_exists($directory))
			throw new Exception("Directory $directory does not exist.");
		
		$conn_name = $this->connectionName();
		$conn = Db::getConnection($conn_name);
		
		$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
		$prefix_len = strlen($prefix);
		
		if (!isset($classname_prefix)) {
			$classname_prefix = isset($conn_name) ? $conn_name . '_' : '';
		}
		
		$rows = $this->rawQuery('SHOW TABLES')
			->execute()->fetchAll();
			
		if (class_exists('Pie_Config')) {
			$ext = Pie_Config::get('pie', 'extensions', 'class', 'php');
		} else {
			$ext = 'php';
		}
			
		$table_classes = array();
		
		foreach ($rows as $row) {
			
			$table_name = $row[0];
			$table_name_base = substr($table_name, $prefix_len);
			$table_name_prefix = substr($table_name, 0, $prefix_len);
			if (empty($table_name_base) or $table_name_prefix != $prefix)
				continue; // no class generated
			
			$class_name = null;
			$base_class_string = $this->codeForModelBaseClass(
				$table_name, $directory, $classname_prefix, $class_name
			); // sets the $class_name variable
			if (empty($class_name))
				continue; // no class generated

			$class_string = <<<EOT
<?php

/**
 * Class representing $table_name_base rows.
 * You can create an object of this class either to
 * access its non-static methods, or to actually
 * represent a $table_name_base row in the $conn_name database.
 *
 * This description should be revised and expanded.
 *
 * @package $conn_name
 */
class $class_name extends Base_$class_name
{
	/**
	 * The setUp() method is called the first time
	 * an object of this class is constructed.
	 */
	function setUp()
	{
		parent::setUp();
		// INSERT YOUR CODE HERE
		// e.g. \$this->hasMany(...) and stuff like that.
	}
	
	/**
	 * Implements the __set_state method, so it can work with
	 * with var_export and be re-imported successfully.
	 */
	static function __set_state(array \$array) {
		\$result = new $class_name();
		foreach(\$array as \$k => \$v)
			\$result->\$k = \$v;
		return \$result;
	}
};
EOT;
		
			$class_name_parts = explode('_', $class_name);
			$class_filename = $directory.DS.implode(DS, $class_name_parts).'.php';
			$base_class_filename = $directory.DS.'Base'.DS.implode(DS, $class_name_parts).'.php';

			// overwrite base class file if necessary, but not the class file
			Db_Utils::saveTextFile(
				$base_class_filename, 
				$base_class_string
			);
			if (! file_exists($class_filename)) {
				Db_Utils::saveTextFile($class_filename, $class_string);
			}
			
			$table_classes[] = $class_name;
		}
		
		// Generate the "module model" base class file
		$table_classes_exported = var_export($table_classes, true);
		if (!empty($conn_name)) {
			$class_name = Db::generateTableClassName($conn_name);
			$class_name_parts = explode('_', $class_name);
			$class_filename = $directory.DS.implode(DS, $class_name_parts).'.php';
			$base_class_filename = $directory.DS.'Base'.DS.implode(DS, $class_name_parts).'.php';

			$base_class_string = <<<EOT
<?php

/**
 * Autogenerated base class for the $conn_name model.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the $class_name.php file.
 *
 * @package $conn_name
 */
abstract class Base_$class_name
{
	static \$table_classes = $table_classes_exported;

	/** @return Db_Mysql */
	static function db()
	{
		return Db::connect('$conn_name');
	}

	static function connectionName()
	{
		return '$conn_name';
	}
};
EOT;

		$class_string = <<<EOT
<?php

/**
 * Static methods for the $conn_name models.
 * This description should be revised and expanded.
 *
 * @package $conn_name
 */
abstract class $class_name extends Base_$class_name
{
	/**
	 * This is where you would place all the
	 * static methods for the models, the ones
	 * that don't strongly pertain to a particular row
	 * or table.
	 */
};
EOT;

			// overwrite base class file if necessary, but not the class file
			Db_Utils::saveTextFile(
				$base_class_filename, 
				$base_class_string
			);
			if (! file_exists($class_filename)) {
				Db_Utils::saveTextFile($class_filename, $class_string);
			}
		}
	}

	/**
	 * Generates code for a base class for the model
	 * 
	 * @param string $table
	 *  The name of the table to generate the code for.
	 * @param string $directory
	 *  The path of the directory in which to place the model code.
	 * @param string $classname_prefix
	 *  The prefix to prepend to the generated class names
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
		$prefix = null)
	{
		if (empty($table_name))
			throw new Exception('table_name parameter is empty', - 2);
		if (empty($directory))
			throw new Exception('directory parameter is empty', - 3);
	
		$conn_name = $this->connectionName();
		$conn = Db::getConnection($conn_name);
		
		if (!isset($prefix)) {
			$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
		}
		if (!empty($prefix)) {
			$prefix_len = strlen($prefix);
			$table_name_base = substr($table_name, $prefix_len);
			$table_name_prefix = substr($table_name, 0, $prefix_len);
			if (empty($table_name_base) or $table_name_prefix != $prefix)
				return ''; // no class generated
		} else {
			$table_name_base = $table_name;
		}
		
		if (empty($classname_prefix))
			$classname_prefix = '';
		if (!isset($class_name)) {
			$class_name_base = Db::generateTableClassName($table_name_base);
			$class_name = ucfirst($classname_prefix) . $class_name_base;
		}
		$table_cols = $this->rawQuery("SHOW COLUMNS FROM $table_name")->execute()->fetchAll();
		// Calculate primary key
		$pk = array();
		foreach ($table_cols as $table_col) {
			if ($table_col['Key'] == 'PRI')
				$pk[] = $table_col['Field'];
		}
		$pk_exported = var_export($pk, true);
		
		// Calculate validation functions
		$functions = array();
		$field_names = array();
		$properties = array();
		$required_field_names = array();
		$magic_field_names = array();
		foreach ($table_cols as $table_col) {
			$field_name = $table_col['Field'];
			$field_names[] = $field_name;
			$field_null = $table_col['Null'] == 'YES' ? true : false;
			$field_default = $table_col['Default'];
			$auto_inc = strpos($table_col['Extra'], 'auto_increment') !== false ? true : false;
			$type = $table_col['Type'];
			$pieces = explode('(', $type);
			if (isset($pieces[1])) {
				$pieces2 = explode(')', $pieces[1]);
				$pieces2_count = count($pieces2);
				if ($pieces2_count > 2) {
					$pieces2 = array(
						implode(')', array_slice($pieces2, 0, -1)), 
						end($pieces2)
					);
				}
			}
			$type_name = $pieces[0];
			if (isset($pieces2)) {
				$type_display_range = $pieces2[0];
				$type_modifiers = $pieces2[1];
				$type_unsigned = (strpos($type_modifiers, 'unsigned') !== false);
			}
			if (! $field_null and ! $auto_inc and 
				(in_array($type_name, array(
					'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'enum'
				)) 
				and $field_default === '')) {
				$required_field_names[] = "'$field_name'";
			}
			
			switch ($type_name) {
				case 'tinyint':
					$type_range_min = $type_unsigned ? 0 : - 128;
					$type_range_max = $type_unsigned ? 255 : 127;
					break;
				case 'smallint':
					$type_range_min = $type_unsigned ? 0 : - 32768;
					$type_range_max = $type_unsigned ? 65535 : 32767;
					break;
				case 'mediumint':
					$type_range_min = $type_unsigned ? 0 : - 8388608;
					$type_range_max = $type_unsigned ? 16777215 : 8388607;
					break;
				case 'int':
					$type_range_min = $type_unsigned ? 0 : - 2147483648;
					$type_range_max = $type_unsigned ? 4294967295 : 2147483647;
					break;
				case 'bigint':
					$type_range_min = $type_unsigned ? 0 : - 9223372036854775808;
					$type_range_max = $type_unsigned ? 18446744073709551615 : 9223372036854775807;
					break;
			}
			
			$null_check = $field_null ? "if (!isset(\$value)) return array('$field_name', \$value);\n\t\t" : '';
			$dbe_check = "if (\$value instanceof Db_Expression) return array('$field_name', \$value);\n\t\t";
			if (! isset($functions["beforeSet_$field_name"]))
				$functions["beforeSet_$field_name"] = array();
			switch (strtolower($type_name)) {
				case 'tinyint':
				case 'smallint':
				case 'int':
				case 'mediumint':
				case 'bigint':
					$properties[]="int \$$field_name";
					$functions["beforeSet_$field_name"][] = <<<EOT
		{$null_check}{$dbe_check}if (!is_numeric(\$value) or floor(\$value) != \$value)
			throw new Exception('Non-integer value being assigned to $table_name.$field_name');
		if (\$value < $type_range_min or \$value > $type_range_max)
			throw new Exception('Out-of-range value being assigned to $table_name.$field_name');
EOT;
					break;
				
				case 'enum':
					$properties[]="mixed \$$field_name";
					$functions["beforeSet_$field_name"][] = <<<EOT
		{$null_check}{$dbe_check}if (!in_array(\$value, array($type_display_range)))
			throw new Exception('Out-of-range value being assigned to $table_name.$field_name');
EOT;
					break;
				
				case 'varchar':
				case 'varbinary':
					$properties[]="string \$$field_name";
					$functions["beforeSet_$field_name"][] = <<<EOT
		{$null_check}{$dbe_check}if (!is_string(\$value))
			throw new Exception('Must pass a string to $table_name.$field_name');
		if (strlen(\$value) > $type_display_range)
			throw new Exception('Exceedingly long value being assigned to $table_name.$field_name');
EOT;
					break;
				
				case 'date':
					$properties[]="string \$$field_name";
					$functions["beforeSet_$field_name"][] = <<<EOT
		{$null_check}{$dbe_check}\$date = date_parse(\$value);
		if (!empty(\$date['errors']))
			throw new Exception("Date \$value in incorrect format being assigned to $table_name.$field_name");
		foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as \$v)
			\$\$v = \$date[\$v];
		\$value = sprintf("%04d-%02d-%02d", \$year, \$month, \$day);
EOT;
					break;
				case 'datetime':
					$properties[]="string \$$field_name";
					if ($field_name == 'time_created' or $field_name == 'time_updated')
						$magic_field_names[] = $field_name;
					$functions["beforeSet_$field_name"][] = <<<EOT
       {$null_check}{$dbe_check}\$date = date_parse(\$value);
       if (!empty(\$date['errors']))
           throw new Exception("DateTime \$value in incorrect format being assigned to $table_name.$field_name");
       foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as \$v)
           \$\$v = \$date[\$v];
       \$value = sprintf("%04d-%02d-%02d %02d:%02d:%02d", \$year, \$month, \$day, \$hour, \$minute, \$second);
EOT;
					break;

				case 'timestamp':
					$properties[]="string \$$field_name";
					break;

				case 'decimal':
					$properties[]="float \$$field_name";
					break;

				default:
					$properties[]="mixed \$$field_name";
					break;
			}
			if (! empty($functions["beforeSet_$field_name"]))
				$functions["beforeSet_$field_name"]['return_statement'] = <<<EOT
		return array('$field_name', \$value);
EOT;
		}
		
		$functions['afterSet'] = array();
		$field_names_exported = "\$this->fieldNames()";
		$afterSet_code = <<<EOT
		if (!in_array(\$name, $field_names_exported))
			\$this->notModified(\$name);
EOT;
		$return_statement = <<<EOT
		return \$value;
EOT;
		$functions["afterSet"][] = $afterSet_code;
		$functions['afterSet']['return_statement'] = $return_statement;
		$functions['afterSet']['args'] = '$name, $value';
		
		$functions['beforeSave'] = array();
		if ($required_field_names) {
			$required_fields_string = implode(',', $required_field_names);
			$beforeSave_code = <<<EOT
		if (!\$this->retrieved) {
			foreach (array($required_fields_string) as \$name) {
				if (!isset(\$value[\$name]))
					throw new Exception("The field $table_name.\$name needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
			}
		}
EOT;
			$return_statement = <<<EOT
		return \$value;
EOT;
			$functions["beforeSave"][] = $beforeSave_code;
			$functions['beforeSave']['return_statement'] = $return_statement;
		}
		
		//$functions['beforeSave'] = array();
		if (count($magic_field_names) > 0) {
			$beforeSave_code = '';
			if (in_array('time_created', $magic_field_names))
				$beforeSave_code .= <<<EOT
		if (!\$this->retrieved and !isset(\$value['time_created']))
			\$value['time_created'] = new Db_Expression('CURRENT_TIMESTAMP');			
EOT;
			if (in_array('time_updated', $magic_field_names))
				$beforeSave_code .= <<<EOT
		//if (\$this->retrieved and !isset(\$value['time_updated']))
		// convention: we'll have time_updated = time_created if just created.
			\$value['time_updated'] = new Db_Expression('CURRENT_TIMESTAMP');			
EOT;
			$return_statement = <<<EOT
		return \$value;
EOT;
			$functions['beforeSave'][] = $beforeSave_code;
			$functions['beforeSave']['return_statement'] = $return_statement;
		}
		
		$functions['fieldNames'] = array();
		$fieldNames_exported = Db_Utils::var_export($field_names);
		$fieldNames_code = <<<EOT
		\$field_names = $fieldNames_exported;
		\$result = \$field_names;
		if (!empty(\$table_alias)) {
			\$temp = array();
			foreach (\$result as \$field_name)
				\$temp[] = \$table_alias . '.' . \$field_name;
			\$result = \$temp;
		} 
		if (!empty(\$field_alias_prefix)) {
			\$temp = array();
			reset(\$field_names);
			foreach (\$result as \$field_name) {
				\$temp[\$field_alias_prefix . current(\$field_names)] = \$field_name;
				next(\$field_names);
			}
			\$result = \$temp;
		}
EOT;
		$return_statement = <<<EOT
		return \$result;
EOT;
		$functions['fieldNames'][] = $fieldNames_code;
		$functions['fieldNames']['return_statement'] = $return_statement;
		$functions['fieldNames']['args'] = '$table_alias = null, $field_alias_prefix = null';
		
		$functions_code = array();
		foreach ($functions as $func_name => $func_code) {
			$func_args = isset($func_code['args']) ? $func_code['args'] : '$value';
			$func_code_string = <<<EOT
	function $func_name($func_args)
	{

EOT;
			if (is_array($func_code) and ! empty($func_code)) {
				foreach ($func_code as $key => $code_tool) {
					if (is_string($key))
						continue;
					$func_code_string .= $code_tool;
				}
				$func_code_string .= "\n" . $func_code['return_statement'];
			}
			$func_code_string .= <<<EOT
			
	}
EOT;
			if (! empty($func_code))
				$functions_code[] = $func_code_string;
		}
		$functions_string = implode("\n\n", $functions_code);
	
		$pk_exported_indented = str_replace("\n", "\n\t\t\t", $pk_exported);
		$conn_name_var = var_export($conn_name, true);
		$class_name_var = var_export($class_name, true);

		foreach ($properties as $k => $v) {
			$properties[$k] = " * @property $v"; 
		}
		$field_hints = implode("\n", $properties);
		
		// Here is the base class:
		$base_class_string = <<<EOT
<?php

/**
 * Autogenerated base class representing $table_name_base rows
 * in the $conn_name database.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the $class_name.php file.
 *
 * @package $conn_name
 *
$field_hints
 */
abstract class Base_$class_name extends Db_Row
{
	function setUp()
	{
		\$this->setDb(self::db());
		\$this->setTable(self::table());
		\$this->setPrimaryKey(
			$pk_exported_indented
		);
	}

	static function db()
	{
		return Db::connect($conn_name_var);
	}

	static function table(\$with_db_name = true)
	{
		\$conn = Db::getConnection($conn_name_var);
		\$prefix = empty(\$conn['prefix']) ? '' : \$conn['prefix'];
		\$table_name = \$prefix . '$table_name_base';
		if (!\$with_db_name)
			return \$table_name;
		\$db = Db::connect($conn_name_var);
		return \$db->dbName().'.'.\$table_name;
	}
	
	static function connectionName()
	{
		return $conn_name_var;
	}

	/** @return Db_Query_Mysql */
	static function select(\$fields, \$alias = null)
	{
		if (!isset(\$alias)) \$alias = '';
		\$q = self::db()->select(\$fields, self::table().' '.\$alias);
		\$q->className = $class_name_var;
		return \$q;
	}

	/** @return Db_Query_Mysql */
	static function update(\$alias = null)
	{
		if (!isset(\$alias)) \$alias = '';
		\$q = self::db()->update(self::table().' '.\$alias);
		\$q->className = $class_name_var;
		return \$q;
	}

	/** @return Db_Query_Mysql */
	static function delete(\$table_using = null, \$alias = null)
	{
		if (!isset(\$alias)) \$alias = '';
		\$q = self::db()->delete(self::table().' '.\$alias, \$table_using);
		\$q->className = $class_name_var;
		return \$q;
	}

	/** @return Db_Query_Mysql */
	static function insert(\$fields = array(), \$alias = null)
	{
		if (!isset(\$alias)) \$alias = '';
		\$q = self::db()->insert(self::table().' '.\$alias, \$fields);
		\$q->className = $class_name_var;
		return \$q;
	}

	/** @return Db_Query_Mysql */
	static function insertManyAndExecute(\$records = array(), \$chunk_size = 1, \$alias = null)
	{
		if (!isset(\$alias)) \$alias = '';
		\$q = self::db()->insertManyAndExecute(self::table().' '.\$alias, \$records, \$chunk_size);
		\$q->className = $class_name_var;
		return \$q;
	}
	
$functions_string
};
EOT;
		// Return the base class	
		return $base_class_string; // unless the above line threw an exception
	}
}
