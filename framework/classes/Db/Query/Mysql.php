<?php

/**
 * This class lets you create and use Db queries.
 * @package Db
 */

class Db_Query_Mysql extends Db_Expression implements iDb_Query
{
	/**
	 * The object implementing iDb that this query uses
	 * @var $db Db_Mysql
	 */
	public $db;
	
	/**
	 * The type of query this is (select, insert, etc.)
	 * @var integer
	 */
	public $type;
	
	/**
	 * The name of the class to instantiate when fetching database rows.
	 * @var string
	 */
	public $className;
	
	/**
	 * Clauses that this query has (WHERE, ORDER BY, etc.)
	 *
	 * @var array
	 */
	protected $clauses;
	
	/**
	 * The parameters passed to this query
	 *
	 * @var array
	 */
	public $parameters = array();

	/**
	 * If this query is prepared, this would point to the
	 * PDOStatement object
	 *
	 * @var $statement PDOStatement
	 */
	protected $statement = null;

	/**
	 * The context of the query. Contains the following keys:
	 *  'callback' => the function or method to call back
	 *  'args' => the arguments to pass to that function or method
	 *
	 * @var array
	 */
	protected $context = null;
	
	/**
	 * Strings to replace in the query, if getSQL() or execute() is called
	 */
	protected $replacements = array();

	/**
	 * Constructor
	 *
	 * @param iDb $db
	 *  An instance of a Db adapter
	 * @param int $type
	 *  The type of the query. See class constants beginning with TYPE_ .
	 * @param array $clauses
	 *  The clauses to add to the query right away
	 * @param array $parameters
	 *  The parameters to add to the query right away (to be bound when executing)
	 */
	function __construct (
		iDb $db, 
		$type, 
		array $clauses = array(), 
		array $parameters = array())
	{		
		$this->db = $db;
		$this->type = $type;
		$this->parameters = array();
		foreach ($parameters as $key => $value) {
			if ($value instanceof Db_Expression) {
				if (is_array($value->parameters)) {
					$this->parameters = array_merge(
						$this->parameters, 
						$value->parameters);
				}
			} else {
				$this->parameters[$key] = $value;
			}
		}
	
		$conn = $this->db->connection();
		$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
		$this->replacements = array(
			'{$prefix}' => $prefix
		);
		
		// Put default contents in the clauses
		// in case the query gets run.
		if (count($clauses) > 0) {
			$this->clauses = $clauses;
		} else {
			switch ($type) {
				case Db_Query::TYPE_SELECT:
					$this->clauses = array(
						'SELECT' => '', 
						'FROM' => '', 
						'WHERE' => ''
					);
					break;
				case Db_Query::TYPE_INSERT:
					$this->clauses = array('INTO' => '', 'VALUES' => ''
					);
					break;
				case Db_Query::TYPE_UPDATE:
					$this->clauses = array(
						'UPDATE' => array(),
						'SET' => array()
					);
					break;
				case Db_Query::TYPE_DELETE:
					break;
				case Db_Query::TYPE_RAW:
					break;
				default:
					throw new Exception("Unknown query type", - 1);
			}
		}
	}

	
	/**
	 * Builds the query from the clauses
	 */
	function build ()
	{
		$q = '';
		switch ($this->type) {
			case Db_Query::TYPE_RAW:
				$q = isset($this->clauses['RAW']) 
					? $this->clauses['RAW'] 
					: '';
				break;
			case Db_Query::TYPE_SELECT:
				// SELECT
				$select = empty($this->clauses['SELECT']) ? '*' : $this->clauses['SELECT'];
				// FROM
				if (empty($this->clauses['FROM']))
					throw new Exception("Missing FROM clause in DB query.", -1);
				$from = $this->clauses['FROM'];
				// JOIN
				$join = empty($this->clauses['JOIN']) ? '' : $this->clauses['JOIN'];
				// WHERE
				$where = empty($this->clauses['WHERE']) ? '1' : $this->clauses['WHERE'];
				// GROUP BY
				$groupBy = empty(
					$this->clauses['GROUP BY']) ? '' : 'GROUP BY ' . $this->clauses['GROUP BY'];
				// GROUP BY
				$having = empty($this->clauses['HAVING']) ? '' : 'HAVING ' .
					 $this->clauses['HAVING'];
				// ORDER BY
				$orderBy = empty(
					$this->clauses['ORDER BY']) ? '' : 'ORDER BY ' . $this->clauses['ORDER BY'];
				// LIMIT
				$limit = empty($this->clauses['LIMIT']) ? '' : $this->clauses['LIMIT'];
				$q = "SELECT $select \nFROM $from \n$join \nWHERE $where \n$groupBy \n$having \n$orderBy \n$limit";
				break;
			case Db_Query::TYPE_INSERT:
				// INTO
				if (empty($this->clauses['INTO']))
					throw new Exception("Missing INTO clause in DB query.", -2);
				$into = $this->clauses['INTO'];
				// VALUES
				//if (empty($this->clauses['VALUES']))
				//    throw new Exception("Missing VALUES clause in DB query.", -3);
				$values = $this->clauses['VALUES'];
				if (empty($this->clauses['ON DUPLICATE KEY UPDATE']))
					$onDuplicateKeyUpdate = ''; 
				else
					$onDuplicateKeyUpdate = 'ON DUPLICATE KEY UPDATE ' . $this->clauses['ON DUPLICATE KEY UPDATE'];
				$q = "INSERT INTO \n$into \nVALUES ( $values ) $onDuplicateKeyUpdate";
				break;
			case Db_Query::TYPE_UPDATE:
				// UPDATE
				if (empty($this->clauses['UPDATE']))
					throw new Exception(
						"Missing UPDATE tables clause in DB query.", -2);
				$update = $this->clauses['UPDATE'];
				if (empty($this->clauses['SET']))
					throw new Exception("Missing SET clause in DB query.", -3);
				// JOIN
				$join = empty($this->clauses['JOIN']) 
				 ? '' 
				 : $this->clauses['JOIN'];
				// SET
				$set = $this->clauses['SET'];
				// WHERE
				if (empty($this->clauses['WHERE']))
					$where = '';
				else
					$where = 'WHERE ' . $this->clauses['WHERE'];
				// LIMIT
				$limit = empty($this->clauses['LIMIT']) ? '' : $this->clauses['LIMIT'];
				$q = "UPDATE \n$update \n$join \n$set \n$where \n$limit";
				break;
			case Db_Query::TYPE_DELETE:
				// DELETE
				if (empty($this->clauses['FROM']))
					throw new Exception("Missing FROM clause in DB query.", 
						- 2);
				$from = $this->clauses['FROM'];
				// JOIN
				$join = empty($this->clauses['JOIN']) 
				 ? '' 
				 : $this->clauses['JOIN'];
				// WHERE
				if (empty($this->clauses['WHERE']))
					$where = '';
				else
					$where = 'WHERE ' . $this->clauses['WHERE'];
				// LIMIT
				$limit = empty($this->clauses['LIMIT']) 
				 ? '' 
				 : $this->clauses['LIMIT'];
				$q = "DELETE \nFROM $from \n$join\n$where \n$limit";
				break;
		}
		foreach ($this->replacements as $k => $v) {
			$q = str_replace($k, $v, $q);
		}
		return $q;
	}

	
	function __toString ()
	{
		try {
			$repres = $this->build();
		} catch (Exception $e) {
			return '*****' . $e->getMessage();
		}
		return $repres;
	}

	/**
	 * Gets the SQL that would be executed with the execute() method.
	 * @param callable $callback
	 *  If not set, this function returns the generated SQL string.
	 *  If it is set, this function calls $callback, passing it the SQL
	 *  string, and then returns $this, for chainable interface.
	 *
	 * @return string | Db_Query
	 *  Depends on whether $callback is set or not.
	 */
	function getSQL (
		$callback = null)
	{
		$repres = $this->build();
		foreach ($this->parameters as $key => $value) {
			if ($value instanceof Db_Expression) {
				$value2 = $value;
			} else if (!isset($value)) {
				$value2 = "NULL";
			} else {
				$this->db->pdoConnect();
				$value2 = $this->db->pdo->quote($value);
			}
			// wrong: $repres = str_replace(":$key", "$value2", $repres);
			if (false !== ($pos = strpos($repres, ":$key"))) {
				$pos2 = $pos + strlen(":$key");
				$repres = substr($repres, 0, $pos) . (string)$value2 . substr($repres, $pos2);
			}
		}
		foreach ($this->replacements as $k => $v) {
			$repres = str_replace($k, $v, $repres);
		}
		if (isset($callback)) {
			$args = array($repres);
			Pie::call($callback, $args);
			return $this;
		}
		return $repres;
	}

	/**
	 * Merges additional replacements over the default replacement array,
	 * which is currently just
	 *        array ( 
	 *           '{$prefix}' => $conn['prefix'] 
	 *        )
	 *  The replacements array is used to replace strings in the SQL
	 *  before using it. Watch out, because it may replace more than you want!
	 *
	 * @param array $replacements
	 *  This must be an array.
	 */
	function replace(array $replacements = array())
	{
		$this->replacements = array_merge($this->replacements, $replacements);
	}

	/**
	 * You can bind more parameters to the query manually using this method.
	 * These parameters are bound in the order they are passed to the query.
	 * Here is an example:
	 * $result = $db->select('*', 'foo')
	 *  ->where(array('a' => $a))
	 *  ->andWhere('a = :moo')
	 *  ->bind(array('moo' => $moo))
	 *  ->execute();
	 * 
	 * @param array $parameters
	 *  An associative array of parameters. The query should contain :name,
	 *  where :name is a placeholder for the parameter under the key "name".
	 *  The parameters will be properly escaped.
	 *  You can also have the query contain question marks (the binding is
	 *  done using PDO), but then the order of the parameters matters.
	 * @return Db_Query_Mysql 
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function bind(array $parameters = array())
	{
		$return = clone($this);
		foreach ($parameters as $key => $value) {
			if ($value instanceof Db_Expression) {
				if (is_array($value->parameters)) {
					$return->parameters = array_merge(
						$return->parameters, 
						$value->parameters);
				}
			} else {
				$return->parameters[$key] = $value;
			}
		}
		return $return;
	}

	/**
	 * Executes a query against the database and returns the result set.
	 * 
	 * @param $prepare_statement
	 *  Defaults to false. If true, a PDO statement will be prepared
	 *  from the query before it is executed. It is also saved for
	 *  future invocations to use.
	 *  Do this only if the statement will be executed many times with
	 *  different parameters. Basically you would use ->bind(...) between 
	 *  invocations of ->execute().
	 *
	 * @return Db_Result
	 *  The Db_Result object containing the PDO statement that resulted
	 *  from the query.
	 */
	function execute ($prepare_statement = false)
	{
		if (class_exists('Pie')) {
			$modifications = Pie::event('db/query/execute', array('query' => $this), 'before');
		}
		if (!isset($modifications)) {
			$modifications = array();
		}
		
		$this->db->pdoConnect($modifications);
		
		try {
			if ($this->type == Db_Query::TYPE_RAW) {
				$sql = $this->build();
				$result = $this->db->pdo->query($sql);
			} else {
				if ($prepare_statement) {
					// Prepare the query into a SQL statement
					// this takes two round-trips to the database
					
					// Preparing the statement
					if (!isset($this->statement)) {
						$q = $this->build();
						$this->statement = $this->db->pdo->prepare($q);
						if ($this->statement === false) {
							if (!isset($sql))
								$sql = $this->getSQL();
							if (class_exists('Pie_Exception_DbQuery')) {
								throw new Exception("Query could not be prepared");
							}
							throw new Exception("Query could not be prepared [query was: $sql ]", - 1);
						}
					}

					// Bind the parameters
					foreach ($this->parameters as $key => $value) {
						$this->statement->bindValue($key, $value);
					}

					// Execute the statement
					try {
						$this->statement->execute();
						$stmt = $this->statement;
					} catch (Exception $e) {
						if (class_exists('Pie_Exception_DbQuery')) {
							throw $e;
						}
						if (!isset($sql))
							$sql = $this->getSQL();
						throw new Exception($e->getMessage() . "\n... Query was: $sql", - 1);
					}
				} else {
					// Obtain the full SQL code ourselves
					// and send to the database, without preparing it there.
					$sql = $this->getSQL();
					$stmt = $this->db->pdo->query($sql);
				}
				
				$result = new Db_Result($stmt, $this);
			}
		} catch (Exception $e) {
			if (!isset($sql)) {
				$sql = $this->getSQL();
			}
			if (!class_exists('Pie_Exception_DbQuery')) {
				throw $e;
			}
			throw new Pie_Exception_DbQuery(array(
				'sql' => $sql,
				'message' => $e->getMessage()
			));
		}
			
		return $result;
	}

	/**
	 * Creates a query to select fields from one or more tables.
	 *
	 * @param string|array $fields 
	 *  The fields as strings, or array of alias=>field
	 * @param string|array $tables
	 *  The tables as strings, or array of alias=>table
	 * @param bool $reuse
	 *  If $tables is an array, and select() has
	 *  already been called with the exact table name and alias
	 *  as one of the tables in that array, then
	 *  this table is not appended to the tables list if
	 *  $reuse is true. Otherwise it is. $reuse is true by default.
	 *  This is really just for using in your hooks.
	 * @return Db_Query_Mysql 
	 *  The resulting object implementing iDb_Query.
	 *  You can use it to chain the calls together.
	 */
	function select ($fields, $tables = '', $reuse = true)
	{
		$as = ''; // was: 'AS', but now we made it more standard SQL
		if (is_array($fields)) {
			$fields_list = array();
			foreach ($fields as $alias => $column) {
				if (is_int($alias))
					$fields_list[] = "$column";
				else
					$fields_list[] = "$column $as $alias";
			}
			$fields = implode(', ', $fields_list);
		}
		if (! is_string($fields)) {
			throw new Exception(
				"The fields to select need to be specified correctly.", 
				-1
			);
		}
		
		$return = clone($this);
				
		if (empty($return->clauses['SELECT']))
			$return->clauses['SELECT'] = $fields;
		else
			$return->clauses['SELECT'] .= ", $fields";
		
		if ($reuse)
			$prev_tables_list = explode(',', $return->clauses['FROM']);
		
		if (! empty($tables)) {
			if (is_array($tables)) {
				$tables_list = array();
				foreach ($tables as $alias => $table) {
					if ($table instanceof Db_Expression) {
						$table_string = is_int($alias) ? "($table)" : "($table) $as $alias";
						$this->parameters = array_merge($this->parameters, $table->parameters);
					} else {
						$table_string = is_int($alias) ? "$table" : "$table $as $alias";
					}
					if ($reuse)
						if (in_array($table_string, $prev_tables_list))
							continue;
					$tables_list[] = $table_string;
				}
				$tables = implode(', ', $tables_list);
			}
			if (! is_string($tables)) {
				throw new Exception(
					"The tables to select from need to be specified correctly.", 
				-1);
			}
			
			if (empty($return->clauses['FROM']))
				$return->clauses['FROM'] = $tables;
			else
				$return->clauses['FROM'] .= ", $tables";
		}
		
		return $return;
	}

	/**
	 * Joins another table to use in the query
	 *
	 * @param string $table
	 *  The name of the table. May also be "name AS alias".
	 * @param Db_Expression|array|string $condition
	 *  The condition to join on. Thus, JOIN table ON ($condition)
	 * @param string $join_type
	 *  The string to prepend to JOIN, such as 'INNER', 'LEFT OUTER', etc.
	 * @return Db_Query_Mysql 
	 *  The resulting object implementing iDb_Query
	 */
	function join ($table, $condition, $join_type = 'INNER')
	{
		switch ($this->type) {
			case Db_Query::TYPE_SELECT:
			case Db_Query::TYPE_UPDATE:
				break;
			case Db_Query::TYPE_DELETE:
				if (strpos($this->clauses['FROM'], 'USING ') !== false)
					break;
			default:
				throw new Exception(
					"The JOIN clause does not belong in this context.", - 1);
		}
		
		$return = clone($this);
		
		static $i = 1;
		if (is_array($condition)) {
			$condition_list = array();
			foreach ($condition as $expr => $value) {
				if ($value instanceof Db_Expression) {
					if (is_array($value->parameters)) {
						$return->parameters = array_merge(
							$return->parameters, 
							$value->parameters);
					}
				} else {
					if (preg_match('/\W/', substr($expr, - 1)))
						$condition_list[] = "$expr $value"; else
						$condition_list[] = "$expr = $value";
					++ $i;
				}
			}
			$condition = implode(' AND ', $condition_list);
		} else if ($condition instanceof Db_Expression) {
			if (is_array($condition->parameters)) {
				$return->parameters = array_merge(
					$return->parameters, 
					$condition->parameters);
			}
			$condition = (string) $condition;
		}
		if (! is_string($condition))
			throw new Exception(
				"The JOIN condition needs to be specified correctly.", - 1);
		
		$join = "$join_type JOIN $table ON ($condition)";
		
		if (empty($return->clauses['JOIN']))
			$return->clauses['JOIN'] = $join;
		else
			$return->clauses['JOIN'] .= " \n$join";

		return $return;
	}

	/**
	 * Adds a WHERE clause to a query
	 *
	 * @param Db_Expression|array $criteria
	 *  An associative array of expression => value pairs. 
	 *  The values are automatically escaped using PDO placeholders.
	 *  Or, this could be a Db_Expression object.
	 * @param array $parameters
	 *  If criteria is a string, you might want to include PDO
	 *  placeholders yourself. In this case, place the parameters
	 *  into this array as param => value pairs, but make sure
	 *  the parameter names don't conflict with any others in the query.
	 * @return Db_Query_Mysql 
	 *  The resulting object implementing iDb_Query
	 */
	function where ($criteria, array $parameters = array())
	{
		switch ($this->type) {
			case Db_Query::TYPE_SELECT:
			case Db_Query::TYPE_UPDATE:
			case Db_Query::TYPE_DELETE:
				break;
			default:
				throw new Exception(
					"The WHERE clause does not belong in this context.", 
				-1);
		}
		$criteria = $this->criteria_internal($criteria, $parameters);
		if (! is_string($criteria))
			throw new Exception(
				"The WHERE criteria need to be specified correctly.", - 1);
		
		$return = clone($this);
		
		if (empty($return->clauses['WHERE']))
			$return->clauses['WHERE'] = "$criteria";
		else
			$return->clauses['WHERE'] = '(' . $return->clauses['WHERE'] . ") AND ($criteria)";
		
		return $return;
	}

	/**
	 * Alias for where()
	 *
	 * @param Db_Expression|string $criteria
	 * @return Db_Query_Mysql
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function andWhere ($criteria)
	{
		if (empty($this->clauses['WHERE']))
			throw new Exception(
				"Don't call andWhere() when you haven't called where() yet", 
			-1);
		
		return $this->where($criteria);
	}

	/**
	 * Adds another expression to the WHERE clause of a query,
	 * preceded by the word OR
	 *
	 * @param Db_Expression|array $criteria
	 *  An associative array of expression => value pairs. 
	 *  The values are automatically escaped using PDO placeholders.
	 *  Or, this could be a Db_Expression object.
	 * @param array $parameters
	 *  If criteria is a string, you might want to include PDO
	 *  placeholders yourself. In this case, place the parameters
	 *  into this array as param => value pairs, but make sure
	 *  the parameter names don't conflict with any others in the query.
	 * @return Db_Query_Mysql
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function orWhere ($criteria, array $parameters = array())
	{
		switch ($this->type) {
			case Db_Query::TYPE_SELECT:
			case Db_Query::TYPE_UPDATE:
			case Db_Query::TYPE_DELETE:
				break;
			default:
				throw new Exception(
					"The WHERE clause does not belong in this context.", 
				-1);
		}
		
		if (empty($this->clauses['WHERE']))
			throw new Exception(
				"Don't call orWhere() when you haven't called where() yet");
		
		$criteria = $this->criteria_internal($criteria, $parameters);
		if (! is_string($criteria))
			throw new Exception(
				"The WHERE criteria need to be specified correctly.", - 1);
		
		$return = clone($this);
		
		$return->clauses['WHERE'] = '(' . $return->clauses['WHERE'] . ") OR ($criteria)";
		
		return $return;
	}

	/**
	 * Adds a GROUP BY clause to a query
	 *
	 * @param Db_Expression|string $expression
	 * @return Db_Query_Mysql
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function groupBy ($expression)
	{
		switch ($this->type) {
			case Db_Query::TYPE_SELECT:
				break;
			default:
				throw new Exception(
					"The GROUP BY clause does not belong in this context.", -1);
		}
		
		if ($expression instanceof Db_Expression) {
			if (is_array($expression->parameters)) {
				$return->parameters = array_merge(
					$return->parameters, 
					$expression->parameters);
			}
			$expression = (string) $expression;
		}
		if (! is_string($expression))
			throw new Exception(
				"The GROUP BY expression has to be specified correctly.", 
			-1);
			
		$return = clone($this);
			
		if (empty($return->clauses['GROUP BY']))
			$return->clauses['GROUP BY'] = "$expression";
		else
			$return->clauses['GROUP BY'] .= ", $expression";
		//if (empty($return->clauses['ORDER BY']))
		//	$return->clauses['ORDER BY'] = "NULL"; // to avoid sorting overhead
		return $return;
	}

	/**
	 * Adds a HAVING clause to a query
	 *
	 * @param Db_Expression|array $criteria
	 *  An associative array of expression => value pairs. 
	 *  The values are automatically escaped using PDO placeholders.
	 *  Or, this could be a Db_Expression object.
	 * @param array $parameters
	 *  If criteria is a string, you might want to include PDO
	 *  placeholders yourself. In this case, place the parameters
	 *  into this array as param => value pairs, but make sure
	 *  the parameter names don't conflict with any others in the query.
	 * @return Db_Query_Mysql
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function having ($criteria, array $parameters = array())
	{
		switch ($this->type) {
			case Db_Query::TYPE_SELECT:
				break;
			default:
				throw new Exception(
					"The HAVING clause does not belong in this context.", 
				-1);
		}
		if (empty($this->clauses['GROUP BY']))
			throw new Exception(
				"Don't call having() when you haven't called groupBy() yet", 
			-1);
		
		$return = clone($this);
		
		$criteria = $return->criteria_internal($criteria, $parameters);
		if (! is_string($criteria))
			throw new Exception(
				"The HAVING criteria need to be specified correctly.", - 1);
		
		if (empty($return->clauses['HAVING']))
			$return->clauses['HAVING'] = "$criteria";
		else
			$return->clauses['HAVING'] = '(' . $return->clauses['HAVING'] . ") AND ($criteria)";
		
		return $return;
	}

	
	/**
	 * Adds an ORDER BY clause to the query
	 *
	 * @param Db_Expression|string $expression
	 *  A string or Db_Expression with the expression to order the results by.
	 * @param bool $ascending
	 *  If false, sorts results as ascending, otherwise descending.
	 * @return Db_Query_Mysql 
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function orderBy ($expression, $ascending = true)
	{
		switch ($this->type) {
			case Db_Query::TYPE_SELECT:
				break;
			default:
				throw new Exception(
					"The ORDER BY clause does not belong in this context.", 
				-1);
		}
		
		if ($expression instanceof Db_Expression) {
			if (is_array($expression->parameters)) {
				$this->parameters = array_merge($this->parameters, 
					$expression->parameters);
			}
			$expression = (string) $expression;
		}
		if (! is_string($expression))
			throw new Exception(
				"The ORDER BY expression has to be specified correctly.", 
			-1);
		
		$return = clone($this);
		
		if (is_bool($ascending)) {
			$expression .= $ascending ? ' ASC' : ' DESC';
		} else if (is_string($ascending)) {
			if (strtoupper($ascending) == 'ASC')
				$expression .= ' ASC';
			else if (strtoupper($ascending) == 'DESC')
				$expression .= ' DESC';
		}
		
		if (empty($return->clauses['ORDER BY']) or $return->clauses['ORDER BY'] == 'NULL') {
			$return->clauses['ORDER BY'] = "$expression";
		} else {
			$return->clauses['ORDER BY'] .= ", $expression";
		}
		return $return;
	}

	/**
	 * Adds optional LIMIT and OFFSET clauses to the query
	 *
	 * @param int $limit
	 *  A non-negative integer showing how many rows to return
	 * @param int $offset
	 *  Optional. A non-negative integer showing what row to start the result set with.
	 * @return Db_Query_Mysql
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function limit ($limit, $offset = null)
	{
		if (!is_numeric($limit) or $limit < 0 or floor($limit) != $limit) {
			throw new Exception("The limit must be a non-negative integer");
		}
		if (isset($offset)) {
			if (!is_numeric($offset) or $offset < 0 or floor($offset) != $offset) {
				throw new Exception("The offset must be a non-negative integer");
			}
		}
		switch ($this->type) {
			case Db_Query::TYPE_SELECT:
				
				break;
			case Db_Query::TYPE_UPDATE:
			case Db_Query::TYPE_DELETE:
				if (isset($offset))
					throw new Exception(
						"The LIMIT clause cannot have an OFFSET in this context");
				if ($this->type == Db_Query::TYPE_DELETE
				and strpos($this->clauses['FROM'], 'USING ') === false)
					break;
			default:
				throw new Exception(
					"The LIMIT clause does not belong in this context.", -1);
		}
		
		if (! is_numeric($limit))
			throw new Exception(
				"The limit in LIMIT has to be specified correctly.", - 2);
		if (isset($offset) and ! is_numeric($offset))
			throw new Exception(
				"The offset in LIMIT has to be specified correctly.", - 3);
		
		if (! empty($this->clauses['LIMIT']))
			throw new Exception(
				"The LIMIT clause has already been specified.");
		
		$return = clone($this);
		
		$return->clauses['LIMIT'] = "LIMIT $limit";
		if (isset($offset))
			$return->clauses['LIMIT'] .= " OFFSET $offset";
		
		return $return;
	}

	
	/**
	 * Adds a SET clause to an UPDATE statement
	 *
	 * @param array $updates
	 *  An associative array of column => value pairs. 
	 *  The values are automatically escaped using PDO placeholders.
	 * @return Db_Query_Mysql
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function set (array $updates)
	{
		$return = clone($this);
		
		$updates = $return->set_internal($updates);
		
		if (empty($return->clauses['SET']))
			$return->clauses['SET'] = 'SET ' . $updates;
		else
			$return->clauses['SET'] .= ", $updates";
		return $return;
	}
	
	/**
	 * Fetches an array of database rows matching the query.
	 * If this exact query has already been executed and
	 * fetchAll() has been called on the Db_Result, and
	 * the return value was cached by the Db_Result, then
	 * that cached value is returned.
	 * Otherwise, the query is executed and fetchAll()
	 * is called on the result.
	 * 
	 * See http://us2.php.net/manual/en/pdostatement.fetchall.php
	 * for documentation.
	 * 
	 * @return array
	 */
	function fetchAll(
		$fetch_style = PDO::FETCH_BOTH, 
		$column_index = null,
		array $ctor_args = array())
	{
		$conn_name = $this->db->connectionName();
		if (empty($conn_name))
			$conn_name = 'empty connection name';
		$sql = $this->getSQL();
		if (isset(Db_Query::$cache[$conn_name][$sql]['fetchAll']))
			return Db_Query::$cache[$conn_name][$sql]['fetchAll'];
		$result = $this->execute();
		$arguments = func_get_args();
		return call_user_func_array(array($result, 'fetchAll'), $arguments);
	}
	
	/**
	 * Fetches an array of Db_Row objects (possibly extended).
	 * If this exact query has already been executed and
	 * fetchAll() has been called on the Db_Result, and
	 * the return value was cached by the Db_Result, then
	 * that cached value is returned.
	 * Otherwise, the query is executed and fetchDbRows()
	 * is called on the result.
	 * 
	 * @param string $class_name
	 *  Optional. The name of the class to instantiate and fill objects from.
	 *  Must extend Db_Row. Defaults to $this->className
	 * @param string $fields_prefix
	 *  This is the prefix, if any, to strip out when fetching the rows.
	 * @param string $by_field
	 *  Optional. A field name to index the array by.
	 *  If the field's value is NULL in a given row, that row is just appended
	 *  in the usual way to the array.
	 * @return array
	 */
	function fetchDbRows(
		$class_name = null, 
		$fields_prefix = '',
		$by_field = null)
	{
		$conn_name = $this->db->connectionName();
		if (empty($conn_name)) {
			$conn_name = 'empty connection name';
		}
		$sql = $this->getSQL();
		if (isset(Db_Query::$cache[$conn_name][$sql]['fetchDbRows'])) {
			return Db_Query::$cache[$conn_name][$sql]['fetchDbRows'];
		}
		return $this->execute()->fetchDbRows($class_name, $fields_prefix, $by_field);
	}

	/**
	 * Adds an ON DUPLICATE KEY UPDATE clause to an INSERT statement.
	 * Use only with MySQL.
	 *
	 * @param array $updates
	 *  An associative array of column => value pairs. 
	 *  The values are automatically escaped using PDO placeholders.
	 * @return Db_Query_Mysql 
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function onDuplicateKeyUpdate ($updates)
	{
		$return = clone($this);
		$updates = $return->onDuplicateKeyUpdate_internal($updates);
		
		if (empty($return->clauses['ON DUPLICATE KEY UPDATE']))
			$return->clauses['ON DUPLICATE KEY UPDATE'] = $updates; 
		else
			$return->clauses['ON DUPLICATE KEY UPDATE'] .= ", $updates";
		return $return;
	}

	function setContext(
		$callback, 
		$args = array())
	{
		$this->context = compact('callback', 'args');
	}

	/**
	 * Can only be called if this is a query returned
	 * from a function that was supposed to execute it, but the user
	 * requested a chance to modify it.
	 * For example, Db_Row->getRelated and Db_Row->retrieve.
	 * After calling a chain of methods, call the resume() method
	 * to complete the original function and return the result.
	 */
	function resume()
	{
		if (empty($this->context['callback'])) {
			throw new Exception(
				"Context is empty. Db_Query->resume() can only be called on an intermediate query.", -1);
		}
		$callback = $this->context['callback'];
		if (is_array($callback)) {
			$callback[1] .= '_resume';
		} else {
			$callback .= '_resume';
		}
		$args = empty($this->context['args']) ? array() : $this->context['args'];
		$args[] = $this;
		return call_user_func_array($callback, $args);
	}

	private function criteria_internal ($criteria, $parameters)
	{
		static $i = 1;
		if (is_array($criteria)) {
			$criteria_list = array();
			foreach ($criteria as $expr => $value) {
				if ($value instanceof Db_Expression) {
					if (is_array($value->parameters)) {
						$this->parameters = array_merge($this->parameters, 
							$value->parameters);
					}
					if (preg_match('/\W/', substr($expr, - 1)))
						$criteria_list[] = "$expr ($value)"; else
						$criteria_list[] = "$expr = ($value)";
				} else {
					if (preg_match('/\W/', substr($expr, - 1)))
						$criteria_list[] = "$expr :_where_$i"; else
						$criteria_list[] = "$expr = :_where_$i";
					$this->parameters["_where_$i"] = $value;
					++ $i;
				}
			}
			$criteria = implode(' AND ', $criteria_list);
		} else if ($criteria instanceof Db_Expression) {
			/** @var $criteria Db_Expression */
			if (is_array($criteria->parameters)) {
				$this->parameters = array_merge($this->parameters, 
					$criteria->parameters);
			}
			$criteria = (string) $criteria;
		}
		
		if (is_array($parameters))
			$this->parameters = array_merge($this->parameters, $parameters);
		
		return $criteria;
	}

	private function set_internal ($updates)
	{
		switch ($this->type) {
			case Db_Query::TYPE_UPDATE:
				break;
			default:
				throw new Exception(
					"The SET clause does not belong in this context.", - 1);
		}
		
		static $i = 1;
		if (is_array($updates)) {
			$updates_list = array();
			foreach ($updates as $field => $value) {
				if ($value instanceof Db_Expression) {
					if (is_array($value->parameters)) {
						$this->parameters = array_merge($this->parameters, 
							$value->parameters);
					}
					$updates_list[] = "$field = $value";
				} else {
					$updates_list[] = "$field = :_set_$i";
					$this->parameters["_set_$i"] = $value;
					++ $i;
				}
			}
			if (count($updates_list) > 0)
				$updates = implode(", \n", $updates_list);
			else
				$updates = '';
		}
		if (! is_string($updates))
			throw new Exception(
				"The SET updates need to be specified correctly.", - 1);
		
		return $updates;
	}

	private function onDuplicateKeyUpdate_internal ($updates)
	{
		// TODO: let updates, etc. have EXPRESSIONS
		switch ($this->type) {
			case Db_Query::TYPE_INSERT:
				break;
			default:
				throw new Exception(
					"The ON DUPLICATE KEY UPDATE clause does not belong in this context.", 
				-1);
		}
		
		static $i = 1;
		if (is_array($updates)) {
			$updates_list = array();
			foreach ($updates as $field => $value) {
				if ($value instanceof Db_Expression) {
					if (is_array($value->parameters)) {
						$this->parameters = array_merge($this->parameters, 
							$value->parameters);
					}
					$updates_list[] = "$field = $value";
				} else {
					$updates_list[] = "$field = :_dupUpd_$i";
					$this->parameters["_dupUpd_$i"] = $value;
					++ $i;
				}
			}
			$updates = implode(", ", $updates_list);
		}
		if (! is_string($updates))
			throw new Exception(
				"The ON DUPLICATE KEY updates need to be specified correctly.", 
			-1);
		
		return $updates;
	}
}
