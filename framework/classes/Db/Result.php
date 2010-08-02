<?php

/**
 * This class lets you use Db results from Db queries.
 * @method fetchAll(int $fetch_style = PDO::FETCH_BOTH, int $column_index = 0, array $ctor_args = array())
 * @method fetch(int $fetch_style = PDO::FETCH_BOTH, int $cursor_orientation = PDO::FETCH_ORI_NEXT, int $cursor_offset = 0)
 * @method fetchColumn(int $column_number = 0)
 * @method rowCount()
 * @method columnCount()
 * @package Db
 */

class Db_Result
{
	/**
	 * The PDO statement object that this result uses
	 * @var $stmt PDOStatement
	 */
	public $stmt;
	
	/**
	 * The query that was run to produce this result
	 * @var $query Db_Query_Mysql
	 */
	public $query;
	
	/**
	 * Whether to cache or not
	 *
	 * @var boolean
	 */
	protected $noCache = false;

	/**
	 * Constructor
	 *
	 * @param PDOStatement $stmt
	 *  The PDO statement object that this result uses
	 * @param iDb_Query $query
	 *  The query that was run to produce this result 
	 */
	function __construct (PDOStatement $stmt, iDb_Query $query)
	{
		$this->stmt = $stmt;
		$this->query = $query;
	}

	/**
	 * Turn off automatic caching on fetchAll and fetchDbRows.
	 */
	function noCache()
	{
		$this->noCache = true;
	}

	/**
	 * Fetches an array of Db_Row objects (possibly extended).
	 * You can pass a prefix to strip from the field names.
	 * It will also filter the result.
	 * 
	 * @param string $class_name
	 *  Optional. The name of the class to instantiate and fill objects from.
	 *  Must extend Db_Row. Defaults to $this->query->className
	 * @param string $fields_prefix
	 *  This is the prefix, if any, to strip out when fetching the rows.
	 * @param string $by_field
	 *  Optional. A field name to index the array by.
	 *  If the field's value is NULL in a given row, that row is just appended
	 *  in the usual way to the array.
	 * @return array
	 */
	function fetchDbRows (
		$class_name = null, 
		$fields_prefix = '',
		$by_field = null)
	{
		if (empty($class_name) && isset($this->query)) {
			$class_name = $this->query->className;
		}
		if (empty($class_name)) {
			$class_name = 'Db_Row';
		}
		if ($class_name != 'Db_Row') {
			$parent_classes = class_parents($class_name);
			if (! in_array('Db_Row', $parent_classes))
				throw new Exception("Class $class_name does not extend Db_Row");
		}
		
		// Build an array of DbRow objects
		$rows = array();
		while ($arr = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
			$row = new $class_name(false);
			$row->copyFrom($arr, $fields_prefix, true, false);
			$row->init($this);
			if ($by_field and isset($row->$by_field)) {
				$rows[$row->$by_field] = $row;
			} else {
				$rows[] = $row;
			}
		}
		
		if (!$this->noCache) {
			// cache the result of executing this particular SQL on this db connection
			$conn_name = $this->query->db->connectionName();
			if (empty($conn_name))
				$conn_name = 'empty connection name';
			$sql = $this->query->getSQL();
			Db_Query::$cache[$conn_name][$sql]['fetchDbRows'] = $rows;
		}
		
		return $rows;
	}

	/**
	 * Dumps the result as an HTML table. 
	 * Side effect, though: can't fetch anymore until the cursor is closed.
	 * @return string
	 */
	function __toMarkup ()
	{
		$return = "<table class='dbResultTable'>\n";
		
		try {
			$rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
			$return .= "<tr class='heading'>\n";
			if (count($rows) > 0) {
				foreach ($rows[0] as $key => $value) {
					$return .= '<td>' . htmlentities($key) . '</td>' . "\n";
				}
			} else {
				return "<div class='dbResultTable'>Db_Result contains zero rows.</div>";
			}
			$return .= "</tr>\n";
			foreach ($rows as $row) {
				$return .= "<tr>\n";
				foreach ($row as $key => $value) {
					$return .= '<td>' . htmlentities($value) . '</td>' . "\n";
				}
				$return .= "</tr>\n";
			}
			$return .= "</table>";
			return $return;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Dumps the result as a table in text mode
	 * Side effect, though: can't fetch anymore until the cursor is closed.
	 * @return string
	 */
	function __toString ()
	{
		return "Db_Result";
		try {
			$ob = new Pie_OutputBuffer();
			$rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
			Db::dump_table($rows);
			return $ob->getClean();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Forwards all other calls to the PDOStatement object
	 *
	 * @param string $name 
	 *  The function name
	 * @param array $arguments 
	 *  The arguments
	 */
	function __call ($name, array $arguments)
	{
		$result = call_user_func_array(array($this->stmt, $name), $arguments);
		if ($name == 'fetch' or $name == 'fetchAll') {
			if (!$this->noCache) {
				// cache the result of executing this particular SQL on this db connection
				$conn_name = $this->query->db->connectionName();
				if (empty($conn_name))
					$conn_name = 'empty connection name';
				$sql = $this->query->getSQL();
				Db_Query::$cache[$conn_name][$sql][$name] = $result;
			}
		}
		return $result;
	}
}
;
