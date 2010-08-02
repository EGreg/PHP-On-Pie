<?php

/**
 * Interface that an adapter must support
 * to implement the Db class.
 * @package Db
 */

interface iDb_Query
{
	/**
	 * Constructor
	 *
	 * @param iDb $db
	 *  The database connection
	 * @param int $type
	 *  The type of the query. See class constants beginning with TYPE_ .
	 * @param array $clauses
	 *  The clauses to add to the query right away
	 * @param array $parameters
	 *  The parameters to add to the query right away (to be bound when executing)
	 */
	//function __construct (
	//	iDb $db, 
	//	$type, 
	//	array $clauses = array(), 
	//	array $parameters = array())

	/**
	 * Builds the query from the clauses
	 */
	function build ();
	
	/**
	 * Just builds the query and returns the string that would
	 * be sent to $pdo->prepare().
	 * If this results in an exception, the string will contain
	 * the exception instead.
	 */
	function __toString ();

	/**
	 * Gets the SQL that would be executed with the execute() method.
	 * @param callable $callback
	 *  If not set, this function returns the generated SQL string.
	 *  If it is set, this function calls $callback, passing it the SQL
	 *  string, and then returns $this, for chainable interface.
	 * @return string | Db_Query
	 *  Depends on whether $callback is set or not.
	 */
	function getSQL ($callback = null);

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
	function replace(array $replacements = array());

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
	 * @return iDb_Query 
	 *  The resulting object implementing iDb_Query
	 *  You can use it to chain the calls together.
	 */
	function bind(array $parameters = array());
	
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
	function execute ($prepare_statement = false);
	
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
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function select ($fields, $tables = '', $reuse = true);

	/**
	 * Joins another table to use in the query
	 *
	 * @param string $table
	 *  The name of the table. May also be "name AS alias".
	 * @param Db_Expression|array|string $condition
	 *  The condition to join on. Thus, JOIN table ON ($condition)
	 * @param string $join_type
	 *  The string to prepend to JOIN, such as 'INNER', 'LEFT OUTER', etc.
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function join ($table, $condition, $join_type = 'INNER');

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
	 *  The resulting Db_Query object
	 */
	function where ($criteria, array $parameters = array());

	/**
	 * Alias for where()
	 *
	 * @param Db_Expression|string $criteria
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function andWhere ($criteria);

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
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function orWhere ($criteria, array $parameters = array());

	/**
	 * Adds a GROUP BY clause to a query
	 *
	 * @param Db_Expression|string $expression
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function groupBy ($expression);

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
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function having ($criteria, array $parameters = array());

	
	/**
	 * Adds an ORDER BY clause to the query
	 *
	 * @param Db_Expression|string $expression
	 *  A string or Db_Expression with the expression to order the results by.
	 * @param bool $ascending
	 *  If false, sorts results as ascending, otherwise descending.
	 * @return Db_Query_Mysql 
	 *  The resulting Db_Query object
	 */
	function orderBy ($expression, $ascending = true);

	/**
	 * Adds optional LIMIT and OFFSET clauses to the query
	 *
	 * @param int $limit
	 *  A non-negative integer showing how many rows to return
	 * @param int $offset
	 *  Optional. A non-negative integer showing what row to start the result set with.
	 * @return Db_Mysql_Query 
	 *  The resulting Db_Query object
	 */
	function limit ($limit, $offset = null);

	
	/**
	 * Adds a SET clause to an UPDATE statement
	 *
	 * @param array $updates
	 *  An associative array of column => value pairs. 
	 *  The values are automatically escaped using PDO placeholders.
	 * @return Db_Query 
	 *  The resulting Db_Query object
	 */
	function set (array $updates);

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
		array $ctor_args = array());
		
	/**
	 * Fetches an array of Db_Row objects.
	 * If this exact query has already been executed and
	 * fetchAll() has been called on the Db_Result, and
	 * the return value was cached by the Db_Result, then
	 * that cached value is returned.
	 * Otherwise, the query is executed and fetchDbRows()
	 * is called on the result.
	 * 
	 * @param string $class_name
	 *  The name of the class to instantiate and fill objects from.
	 *  Must extend Db_Row.
	 * @param string $fields_prefix
	 *  This is the prefix, if any, to strip out when fetching the rows.
	 * @return array
	 */
	function fetchDbRows(
		$class_name = 'Db_Row', 
		$fields_prefix = '');

	/**
	 * Adds an ON DUPLICATE KEY UPDATE clause to an INSERT statement.
	 * Use only with MySQL.
	 *
	 * @param array $updates
	 *  An associative array of column => value pairs. 
	 *  The values are automatically escaped using PDO placeholders.
	 * @return Db_Query
	 */
	function onDuplicateKeyUpdate ($updates);

};


/**
 * This class lets you create and use Db queries.
 */

abstract class Db_Query
{	
	/**#@+
	 * Types of queries available right now
	 */
	const TYPE_RAW = 1;
	const TYPE_SELECT = 2;
	const TYPE_INSERT = 3;
	const TYPE_UPDATE = 4;
	const TYPE_DELETE = 5;
	/**#@-*/

	static $cache = array();

}
