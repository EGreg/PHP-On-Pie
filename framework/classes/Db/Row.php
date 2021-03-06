<?php

/**
 * This class lets you use Db rows and object-relational mapping functionality.
 * 
 * 
 * Your model classes should extend this class. For example,
 * class User extends Db_Row.
 * 
 * 
 * When you extend this class, you can also implement the following callbacks.
 * If they exist, Db_Row will call them at the appropriate time.
 * <ul>
 *  <li>
 *     <b>setUp</b>
 *     Called by the constructor to set up the information for
 *     the fields and table relations.
 *  </li>
 *  <li>
 *     <b>beforeRetrieve($search_criteria, $options)</b>
 *     Called by retrieveOrSave() and retrieve() methods before retrieving a row.
 *     $search_criteria is an associative array of modified fields and their values.
 *     Return either an array of objects extending Db_Row, in which case no database SELECT is done,
 *     or return the $search_criteria to use for the database search.
 *     To totally cancel retrieval from the database, return null or an empty array.
 *     Typically, you would use this function to retrieve from a cache, and call
 *     calculatePKValue() to generate the key for the cache.
 *  </li>
 *  <li>
 *     <b>beforeGetRelated($relation_name, $fields, $inputs, $options)</b>
 *     Called by getRelated method before trying to get related rows.
 *     $relation_name, $inputs and $fields are the parameters passed to getRelated.
 *     If return value is set, then that is what getRelated returns immediately
 *     after beforeGetRelated returns.
 *     Typically, you would use this function to retrieve from a cache.
 *  </li>
 *  <li>
 *     <b>beforeGetRelatedExecute($relation_name, $query, $options)</b>
 *     Called by getRelated() method before executing the Db_Query to get related rows.
 *     It is passed the $relation_name, the $query, and any options passed to getRelated().
 *     This function should return the Db_Query to execute.
 *  </li>
 *  <li>
 *     <b>beforeSave($modified_fields)</b>
 *     Called by save() method before saving the row.
 *     $modified_values is an associative array of modified fields and their values.
 *     Return the fields that should still be saved after beforeSave returns.
 *     To cancel saving into the database, return null or an empty array.
 *     Typically, you would use this function to save into a cache, and call
 *     calculatePKValue() to generate the key for the cache.
 *  </li>
 *  <li>
 *     <b>beforeSaveExecute($query)</b>
 *     Called by save() method before executing the Db_Query to save.
 *     It is passed the $query. This function should return the Db_Query to execute.
 *  </li>
 *  <li>
 *     <b>afterSaveExecute($result)</b>
 *     Called by save() method after executing the Db_Query to save.
 *     It is passed the $result. This function can analyze the result & take further action.
 *     It should return the $result back to the caller.
 *  </li>
 *  <li>
 *     <b>beforeDelete($pk)</b>
 *     Called by delete() method before saving the row.
 *     $pk is an associative array representing the primary key of the row to delete.
 *     Return a boolean indicating whether or not to delete.
 *  </li>
 *  <li>
 *     <b>beforeDeleteExecute($query)</b>
 *     Called by delete() method before executing the Db_Query to save.
 *     It is passed the $query. This function should return the Db_Query to execute.
 *  </li>
 *  <li>
 *     <b>afterDeletexecute($result)</b>
 *     Called by delete() method after executing the Db_Query to save.
 *     It is passed the $result. This function can analyze the result & take further action.
 *     It should return the $result back to the caller.
 *  </li>
 *  <li>
 *     <b>beforeSet_$name($value)</b>
 *     Called before the field named $name is set.
 *     Return <i>array($internal_name, $value)</i> of the field.
 *     Handy when changing the name of the field inside the database layer,
 *     as well as validating the value, etc.
 *  </li>
 *  <li>
 *     <b>afterSet_$name($value)</b>
 *     Called after the field named $name has been set.
 *  </li>
 *  <li>
 *     <b>afterSet($name, $value)</b>
 *     Called after any field has been set, 
 *     and after specific afterSet_$name was called.
 *     Usually used to call things like notModified($name);
 *  </li>
 *  <li>
 *     <b>beforeGet_$name()</b>
 *     Called right before returning the name of the field called $name.
 *     If it's defined, whatever this function returns, the user receives.
 *     There is no real need for beforeGet($name) as a counterpart 
 *     to beforeSet($name, $value), as there is no need to change the $name.
 *     You can obtain the <i>value</i> of the field, and return it.
 *  </li>
 *  <li>
 *     <b>isset_$name</b>
 *     Called when checking if the field called $name is set and not null.
 *     Return true or false.
 *     Your function should probably make use of $this->fields directly here.
 *  </li>
 *  <li>
 *     <b>unset_$name</b>
 *     Called when someone wants to unset the field called $name. 
 *     Your function should probably make use of $this->fields directly here.
 *  </li>
 * </ul>
 * @package Db
 */

class Db_Row implements Iterator
{
	/**
	 * Whether this Db_Row was retrieved or not.
	 * The save() method uses this to decide whether to insert or update.
	 * @var bool
	 */
	protected $retrieved;
	
	/**
	 * The value of the primary key of the row
	 * Is set automatically if the Db_Row was fetched from a Db_Result.
	 * @var array
	 */
	protected $pkValue;
	
	/**
	 * Array of settings set up for a particular class
	 * that extends Db_Row.
	 * TODO: Can be abstracted into a DbTable class later.
	 * 
	 * @var array
	 */
	protected static $setUp;
	
	/**
	 * The fields of the row
	 * @var $fields
	 */
	public $fields = array();
	
	/**
	 * Stores whether the fields were modified
	 * @var $fields_modified
	 */
	protected $fields_modified = array();
	
	/**
	 * Used for setting and getting parameters on this Db_Row object
	 * which are not to be saved/retrieved to the db.
	 */
	protected $p;

	/**
	 * Constructor
	 *
	 * @param Db_Result $result
	 *  The result that produced this row through fetchDbRows 
	 * @param bool $doInit
	 *  Whether to initialize the row. The reason this is here
	 *  is that passing object arguments to the constructor by using
	 *  PDOStatement::setFetchMode() causes a memory leak.
	 *  This is only set to false by Db_Result::fetchDbRows(),
	 *  which subsequently calls init() by itself.
	 *  As a user of this class, don't override this default value.
	 */
	function __construct ($doInit = true)
	{
		if ($doInit) {
			$this->init();
		}
	}

	/**
	 * Call this function to (re-)initialize the object.
	 * Typically should only be called from the constructor.
	 *
	 * @param Db_Result $result
	 *  The result that produced this row through fetchDbRows
	 */
	function init ($result = null)
	{
		$mySetUp = & $this->getSetUp();
		
		// Store whether this Db_Row was retrieved or not
		if (!isset($this->retrieved)) {
			$this->retrieved = ! empty($result);
		}
		
		// Set the default DB name, if needed and there
		if (empty($mySetUp['db'])) {
			if (!empty($result))
				if (!empty($result->query))
					$this->setDb($result->query->db);
		}
			
		// Set the default table name, if needed
		$class_name = get_class($this);
		if (empty($mySetUp['table'])) {
			$parts = explode('_', $class_name, 2);
			//$class_prefix = reset($parts);
			$table_name = end($parts);
			$table_name = strtolower($table_name);
			$this->setTable($table_name);
		}
		
		// Set up the default 'relations' and 'relations_many' arrays
		if (empty($mySetUp['relations']))
			$mySetUp['relations'] = array();
		if (empty($mySetUp['relations_many']))
			$mySetUp['relations_many'] = array();
		if (empty($mySetUp['relations_class_name']))
			$mySetUp['relations_class_name'] = array();
		if (empty($mySetUp['relations_alias']))
			$mySetUp['relations_alias'] = array();
		
		// Perform any other set-up!
		if (empty($mySetUp['setUp'])) {
			$callback = array($this, "setUp");
			if (is_callable($callback))
				call_user_func($callback);
			$mySetUp['setUp'] = true;
		}
		
		// Set the primary key, if this Db_Row came from a Db_Result
		if (! empty($result)) {
			$pk = $this->getPrimaryKey();
			if (is_array($pk)) {
				foreach ($pk as $field_name) {
					if (!array_key_exists($field_name, $this->fields)) {
						$get_class = get_class($this);
						$backtrace = debug_backtrace();
						$function = $line = $class = null;
						if (isset($backtrace[1]['function'])) {
							$function = $backtrace[1]['function'];
						}
						if (isset($backtrace[1]['line'])) {
							$line = $backtrace[1]['line'];
						}
						if (isset($backtrace[1]['class'])) {
							$class = $backtrace[1]['class'];
						}
						throw new Exception(
							"$get_class does not have $field_name field set, "
							. "called in $class::$function (line $line in function)."
						);
					}
					$this->pkValue[$field_name] = isset($this->fields[$field_name])
						? $this->fields[$field_name]
						: null;
				}
			}
		}
		
		// This record was just instantiated, so 
		// mark all fields as not modified.
		if (is_array($this->fields))
			foreach ($this->fields as $name => $value)
				$this->fields_modified[$name] = false;
	}
	
	/**
	 * Default implementation, does nothing
	 */
	function setUp ()
	{
	
	}
	
	/**
	 * Converts joins to an array of relations
	 * Used by hasOne and hasMany.
	 * @param array $joins
	 *  An array of associative arrays, which represent joins.
	 *  This is used internally, and has rules
	 *  described in hasOne and hasMany.
	 * @param array $aliases,
	 *  Optional. An associative array mapping aliases to class names.
	 *  Once set up, the aliases can be used in the join arrays instead of
	 *  the class names.
	 * @return array
	 *  An array of relations that were generated
	 */
	protected static function joinsToRelations($aliases = null, $joins = array())
	{
		if (empty($aliases)) {
			$aliases = array();
		}
		
		$relations = array();
		foreach ($joins as $join) {
			if (empty($join)) {
				continue;
			}
			
			$join_r = array();
			$this_r = '__this_table';
			foreach ($join as $k => $v) {
				$k = str_replace('{$this}', $this_r, $k);
				$v = str_replace('{$this}', $this_r, $v);
				$join_r[$k] = $v;
			}
			
			$v = reset($join_r);
			$k = key($join_r);
			list($class1) = explode('.', $v);
			list($class2) = explode('.', $k);
			if (isset($aliases[$class1])) {
				$alias1 = $class1;
				$class1 = $aliases[$alias1];
				$table1 = call_user_func(array($class1, 'table')) . $alias1;
			} else {
				$table1 = call_user_func(array($class1, 'table'));
			}
			if (isset($aliases[$class2])) {
				$alias2 = $class2;
				$class2 = $aliases[$alias2];
				$table2 = call_user_func(array($class2, 'table')) . $alias2;
			} else {
				$table2 = call_user_func(array($class2, 'table'));
			}
			// Make a new Db_Relation with this info (join type: LEFT)
			$relations[] = new Db_Relation($table1, $join_r, $table2);
		}
		return $relations;
	}
	
	/**
	 * Set up a relation where at most one object is returned.
	 * For a more complex version, see hasOneEx.
	 *
	 * @param string $relation_name
	 *  The name of the relation. For example, "mother" or "primary_email"
	 * @param array $aliases,
	 *  Required. An associative array mapping aliases to class names.
	 *  Once set up, the aliases can be used in the join arrays instead of
	 *  the class names. 
	 *  The value of the last entry of this array is the name of the ORM class
	 *  that will hold each row of the result.
	 * @param array $join1
	 *  An array describing a relation between one table and another.
	 *  Each pair must be of the form "a.b" => "c.d", where a and c
	 *  are names of classes extending Db_Row, or their aliases from $aliases.
	 *  If a join array has more than one pair, the a and c must be the
	 *  same for each pair in the join array.
	 *
	 *  You can have as many of these as you want. A Db_Relation will be
	 *  built that will build a tree of these for you.
	 */
	function hasOne(
		$relation_name,
		$aliases,
		$join1,
		$join2 = null)
	{	
		$args = func_get_args();
		array_unshift($args, get_class($this));
		call_user_func_array(array('Db_Row', 'hasOneFromClass'), $args);
	}
	
	/**
	 * Set up a relation where at most one object is returned.
	 * For a more complex version, see hasOneFromClassEx.
	 *
	 * @param string $from_class_name
	 *  The name of the ORM class on which to set the relation
	 * @param string $relation_name
	 *  The name of the relation. For example, "mother" or "primary_email"
	 * @param array $aliases,
	 *  Required. An associative array mapping aliases to class names.
	 *  Once set up, the aliases can be used in the join arrays instead of
	 *  the class names. 
	 *  The value of the last entry of this array is the name of the ORM class
	 *  that will hold each row of the result.
	 * @param array $join1
	 *  An array describing a relation between one table and another.
	 *  Each pair must be of the form "a.b" => "c.d", where a and c
	 *  are names of classes extending Db_Row, or their aliases from $aliases.
	 *  If a join array has more than one pair, the a and c must be the
	 *  same for each pair in the join array.
	 *
	 *  You can have as many of these as you want. A Db_Relation will be
	 *  built that will build a tree of these for you.
	 */
	function hasOneFromClass(
		$relation_name,
		$aliases,
		$join1,
		$join2 = null)
	{	
		// Build the Db_Relations to pass to hasManyEx
		self::joinsToRelations($aliases, array_slice(func_get_args(), 3));
		$to_class_name = end($aliases);
		
		$params = array(
			get_class($this),
			$relation_name,
			$to_class_name,
			'__this_table',
		);
		$args = func_get_args();
		$params = array_merge($params, array_slice($args, 2));
		call_user_func_array(array('Db_Row', 'hasOneFromClassEx'), $params);
	}

	/**
	 * Set up a relation where at most one object is returned.
	 *
	 * @param string $relation_name
	 *  The name of the relation. For example, "mother" or "primary_email"
	 * @param string $to_class_name
	 *  The name of the ORM class which extends Db_Row, to load the result into.
	 * @param string $alias
	 *  The table name or alias that will refer to the table in the query
	 *  corresponding to $this object.
	 * @param Db_Relation $relation
	 *  The relation between two or more tables, or the table
	 *  with itself.
	 *  You can pass as many Db_Relations as necessary and they are combined
	 *  using Db_Relation's constructor.
	 *  The only valid relations are the ones
	 *  which have a single root foreign_table.
	 */
	function hasOneEx (
		$relation_name, 
		$to_class_name, 
		$alias, 
		Db_Relation $relation, 
		Db_Relation $relation2 = null)
	{
		$args = func_get_args();
		array_unshift($args, get_class($this));
		call_user_func_array(array('Db_Row', 'hasOneFromClassEx'), $args);
	}

	
	/**
	 * Sets up a relation where an array is returned.
	 * For a more complex version, see hasManyEx.
	 *
	 * @param string $relation_name
	 *  The name of the relation. For example, "tags" or  "reviews"
	 * @param array $aliases,
	 *  Required. An associative array mapping aliases to class names.
	 *  Once set up, the aliases can be used in the join arrays instead of
	 *  the class names. 
	 *  The value of the last entry of this array is the name of the ORM class
	 *  that will hold each row of the result.
	 * @param array $join1
	 *  An array describing a relation between one table and another.
	 *  Each pair must be of the form "a.b" => "c.d", where a and c
	 *  are names of classes extending Db_Row, or their aliases from $aliases.
	 *  If a join array has more than one pair, the a and c must be the
	 *  same for each pair in the join array.
	 *
	 *  You can have as many of these as you want. A Db_Relation will be
	 *  built that will build a tree of these for you.
	 */
	function hasMany(
		$relation_name,
		$aliases,
		$join1,
		$join2 = null)
	{	
		$params = func_get_args();
		array_unshift($params, get_class($this));
		call_user_func_array(array('Db_Row', 'hasManyFromClass'), $params);
	}
	
	/**
	 * Sets up a relation where an array is returned.
	 * For a more complex version, see hasManyFromClassEx.
	 *
	 * @param string $from_class_name
	 *  The name of the ORM class on which to set the relation
	 * @param string $relation_name
	 *  The name of the relation. For example, "tags" or  "reviews"
	 * @param array $aliases,
	 *  Required. An associative array mapping aliases to class names.
	 *  Once set up, the aliases can be used in the join arrays instead of
	 *  the class names. 
	 *  The value of the last entry of this array is the name of the ORM class
	 *  that will hold each row of the result.
	 * @param array $join1
	 *  An array describing a relation between one table and another.
	 *  Each pair must be of the form "a.b" => "c.d", where a and c
	 *  are names of classes extending Db_Row, or their aliases from $aliases.
	 *  If a join array has more than one pair, the a and c must be the
	 *  same for each pair in the join array.
	 *
	 *  You can have as many of these as you want. A Db_Relation will be
	 *  built that will build a tree of these for you.
	 */
	function hasManyFromClass(
		$class_name,
		$relation_name,
		$aliases,
		$join1,
		$join2 = null)
	{	
		// Build the relations to pass to hasManyEx
		self::joinsToRelations($aliases, array_slice(func_get_args(), 3));
		$to_class_name = end($aliases);

		$params = array(
			get_class($this),
			$relation_name,
			$to_class_name,
			'__this_table',
		);
		$args = func_get_args();
		$params = array_merge($params, array_slice($args, 3));
		call_user_func_array(array('Db_Row', 'hasManyFromClassEx'), $params);
	}
	
	/**
	 * Set up a relation where an array is returned.
	 *
	 * @param string $relation_name
	 *  The name of the relation. For example, "tags" or  "reviews"
	 * @param string $to_class_name
	 *  The name of the ORM class which extends Db_Row, to load the result into.
	 * @param string $alias
	 *  The table name or alias that will refer to the table in the query
	 *  corresponding to $this object.
	 * @param Db_Relation $relation
	 *  The relation between two or more tables, or the table
	 *  with itself.
	 *  You can pass as many Db_Relations as necessary and they are combined
	 *  using Db_Relation's constructor.
	 *  The only valid relations are the ones which have a single root foreign_table.
	 */
	function hasManyEx (
		$relation_name, 
		$to_class_name, 
		$alias, 
		Db_Relation $relation, 
		Db_Relation $relation2 = null)
	{
		$args = func_get_args();
		array_unshift($args, get_class($this));
		call_user_func_array(array('Db_Row', 'hasManyFromClassEx'), $args);
	}

	/**
	 * Set up a relation where at most one object is returned.
	 *
	 * @param string $from_class_name
	 *  The name of the ORM class on which to set the relation
	 * @param string $relation_name
	 *  The name of the relation. For example, "Mother" or  "Primary Email"
	 * @param string $to_class_name
	 *  The name of the ORM class which extends Db_Row, to load the result into.
	 * @param string $alias
	 *  The table name or alias that will refer to the table in the query
	 *  corresponding to $this object.
	 * @param Db_Relation $relation
	 *  The relation between two or more tables, or the table
	 *  with itself.
	 *  You can pass as many Db_Relations as necessary and they are combined
	 *  using Db_Relation's constructor.
	 *  The only valid relations are the ones which have a single root foreign_table.
	 */
	static function hasOneFromClassEx (
		$from_class_name,
		$relation_name, 
		$to_class_name, 
		$alias, 
		Db_Relation $relation, 
		Db_Relation $relation2 = null)
	{
		$args = func_get_args();
		$count = count($args);
		$relations = array();
		for ($i = 4; $i < $count; ++ $i)
			$relations[] = $args[$i];
		$relation = new Db_Relation($relations);

		// Add the relations
		$mySetUp = & self::getSetUpFromClass($from_class_name);
		$mySetUp['relations'][$relation_name] = $relation;
		$mySetUp['relations_many'][$relation_name] = false;
		$mySetUp['relations_class_name'][$relation_name] = $to_class_name;
		$mySetUp['relations_alias'][$relation_name] = $alias;
	}

	/**
	 * Set up a relation for another table, where an array is returned.
	 *
	 * @param string $from_class_name
	 *  The name of the ORM class on which to set the relation
	 * @param string $relation_name
	 *  The name of the relation. For example, "Tags" or  "Reviews"
	 * @param string $to_class_name
	 *  The name of the ORM class which extends Db_Row, to load the result into.
	 * @param string $alias
	 *  The table name or alias that will refer to the table in the query
	 *  corresponding to the other object.
	 * @param Db_Relation $relation
	 *  The relation between two or more tables, or the table
	 *  with itself.
	 *  You can pass as many Db_Relations as necessary and they are combined
	 *  using Db_Relation's constructor.
	 *  The only valid relations are the ones
	 *  which have a single root foreign_table, with a
	 *  foreign_class_name specified. That is the class of the
	 *  object created when getRelated(...) is called.
	 */
	static function hasManyFromClassEx (
		$from_class_name,
		$relation_name, 
		$to_class_name,
		$from_alias, 
		Db_Relation $relation, 
		Db_Relation $relation2 = null)
	{
		$args = func_get_args();
		$count = count($args);
		$relations = array();
		for ($i = 4; $i < $count; ++ $i)
			$relations[] = $args[$i];
		$relation = new Db_Relation($relations);
		
		// Add the relations
		if (! isset(self::$setUp[$from_class_name]))
			self::$setUp[$from_class_name] = array();
		$mySetUp =& self::$setUp[$from_class_name];
		$mySetUp['relations'][$relation_name] = $relation;
		$mySetUp['relations_many'][$relation_name] = true;
		$mySetUp['relations_class_name'][$relation_name] = $to_class_name;
		$mySetUp['relations_alias'][$relation_name] = $from_alias;
	}

	/**
	 * Returns whether this Db_Row contains information retrieved from the database.
	 * @param bool $new_value
	 *  If set, then this function sets the "retrieved" status to the new value.
	 *  Otherwise, it just gets the "retrieved" status of the row.
	 * @return bool
	 *  Whether the row is marked as retrieved from the Db.
	 */
	function wasRetrieved ($new_value = null)
	{
		if (isset($new_value))
			$this->retrieved = $new_value;
		return $this->retrieved;
	}

	/**
	 * Marks a particular field as not modified since retrieval or creation of the object.
	 * @param string $field_name
	 *  The name of the field
	 * @return bool
	 *  Whether the field with that name was modified in the first place.
	 */
	function notModified ($field_name)
	{
		if (empty($this->fields_modified[$field_name]))
			return false;
		$this->fields_modified[$field_name] = false;
		return true;
	}

	/**
	 * Returns whether a particular field was modified since retrieval or creation of the object.
	 * @param string $field_name
	 *  The name of the field
	 * @return bool
	 *  Whether the field with that name was modified in the first place.
	 */
	function wasModified ($field_name = null)
	{
		if (! isset($field_name)) {
			foreach ($this->fields_modified as $key => $value)
				if (! empty($value))
					return true;
			return false;
		}
		if (empty($this->fields_modified[$field_name]))
			return false;
		return true;
	}

	/**
	 * Gets the primary key of the table
	 *
	 * @return array
	 *  An array naming all the fields that comprise the
	 *  primary key index, in the order they appear in the key.
	 */
	function getPrimaryKey ()
	{
		$mySetUp = $this->getSetUp();
		return isset($mySetUp['primaryKey']) ? $mySetUp['primaryKey'] : null;
	}

	/**
	 * Sets up the primary key of the table
	 *
	 * @param array $primaryKey
	 *  An array naming all the fields that comprise the
	 *  primary key index, in the order they appear in the key.
	 */
	function setPrimaryKey (array $primaryKey)
	{
		$mySetUp = & $this->getSetUp();
		$mySetUp['primaryKey'] = $primaryKey;
	}

	/**
	 * Sets the database to operate on
	 *
	 * @param iDb $db
	 */
	public function setDb (iDb $db)
	{
		$mySetUp = & $this->getSetUp();
		$mySetUp['db'] = $db;
	}

	
	/**
	 * Gets the SetUp for this object's class
	 *
	 * @return &array 
	 * the setUp array
	 */
	function &getSetUp ()
	{
		$class_name = get_class($this);

		if (! isset(self::$setUp[$class_name]))
			self::$setUp[$class_name] = array();
		
		return self::$setUp[$class_name];		
	}
	
	/**
	 * Gets the setUp for a class
	 * @param string $class_name
	 *
	 * @return &array 
	 * the setUp array
	 */
	static function &getSetUpFromClass($class_name)
	{
		if (! isset(self::$setUp[$class_name]))
			self::$setUp[$class_name] = array();
		
		return self::$setUp[$class_name];
	}

	/**
	 * Gets the database to operate on, associated with this row
	 *
	 * @return Db_Mysql
	 */
	function getDb ()
	{
		$mySetUp = $this->getSetUp();
		return isset($mySetUp['db']) ? $mySetUp['db'] : false;
	}

	/**
	 * Gets the database to operateon, associated with this row
	 *
	 * @return Db
	 */
	static function getDbFromClassName ($class_name)
	{
		if (! isset(self::$setUp[$class_name]))
			self::$setUp[$class_name] = array();
		$mySetUp = self::$setUp[$class_name];
		return isset($mySetUp['db']) ? $mySetUp['db'] : false;
	}

	/**
	 * Sets the table to operate on 
	 *
	 * @param string $table_name
	 */
	public function setTable ($table_name)
	{
		$mySetUp = & $this->getSetUp();
		$mySetUp['table'] = $table_name;
	}

	/**
	 * Gets the table that was set to operate on
	 *
	 * @return string
	 */
	function getTable ()
	{
		$mySetUp = $this->getSetUp();
		return $mySetUp['table'];
	}

	/**
	 * Gets the primary key's value for this record, if it was retrieved.
	 * If the record was not retrieved, the primary key's value should be empty.
	 *
	 * @return array
	 *  An associative array with keys being all the fields that comprise the
	 *  primary key index, in the order they appear in the key.
	 *  The values are the values at the time the record was retrieved.
	 */
	function getPKValue ()
	{
		return $this->pkValue;
	}

	/**
	 * Calculate the primary key's value for this record
	 * Different from getPKValue in that it returns the CURRENT
	 * values of all the fields named in the primary key index.
	 * This can be called even if the Db_Row was not retrieved,
	 * and typically is used for caching purposes.
	 * 
	 * @return array
	 *  An associative array naming all the fields that comprise the
	 *  primary key index, in the order they appear in the key.
	 * @return false
	 *  Returns false if even one of the fields comprising the primary key is not set.
	 */
	function calculatePKValue ()
	{
		$return = array();
		$pk = $this->getPrimaryKey();
		foreach ($pk as $field_name) {
			if (! isset($this->$field_name))
				return false;
			$return[$field_name] = $this->$field_name;
		}
		return $return;
	}

	/**
	 * Sets the primary key's value for this record.
	 * You should really call this only on Db_Row objects
	 * that were not extended by another class.
	 */
	function setPKValue ($new_pk_value)
	{
		if (! is_array($new_pk_value))
			throw new Exception("setPKValue expects an array", - 1);
		$this->pkValue = $new_pk_value;
	}

	/**
	 * Sets a column in the row
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	function __set ($name, $value)
	{
		$name_internal = $name;
	
		$callback = array($this, "beforeSet_$name");
		if (is_callable($callback))
			list ($name_internal, $value) = call_user_func($callback, $value);
		
		$this->fields[$name_internal] = $value;
		$this->fields_modified[$name_internal] = true;
		
		$callback = array($this, "afterSet_$name");
		if (is_callable($callback))
			$value = call_user_func($callback, $value);

		$callback = array($this, "afterSet");
		if (is_callable($callback))
			$value = call_user_func($callback, $name, $value);
	}

	/**
	 * Gets a column in the row
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get ($name)
	{
		$callback = array($this, "beforeGet_$name");
		if (is_callable($callback))
			return call_user_func($callback);
		
		if (array_key_exists($name, $this->fields)) {
			return $this->fields[$name]; 
		} else {
			$get_class = get_class($this);
			$backtrace = debug_backtrace();
			$function = $line = $class = null;
			if (isset($backtrace[1]['function'])) {
				$function = $backtrace[1]['function'];
			}
			if (isset($backtrace[1]['line'])) {
				$line = $backtrace[1]['line'];
			}
			if (isset($backtrace[1]['class'])) {
				$class = $backtrace[1]['class'];
			}
			throw new Exception(
				"$get_class does not have $name field set, "
				. "called in $class::$function (line $line in function)."
			);
			return null;
		}
	}

	/**
	 * Returns whether a column in the row is set
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __isset ($name)
	{
		$callback = array($this, "isset_$name");
		if (is_callable($callback))
			return call_user_func($callback);
		return isset($this->fields[$name]);
	}

	/**
	 * Unsets a column in the row.
	 * This only affects the PHP object, not the database record.
	 * If the PHP object is saved, it simply does not affect that column in the database.
	 *
	 * @param string $name
	 */
	function __unset ($name)
	{
		$callback = array($this, "unset_$name"
		);
		if (is_callable($callback)) {
			call_user_func($callback);
		} else {
			unset($this->fields[$name]);
		}
	}

	/**
	 * Get some records in a related table using foreign keys
	 *
	 * @param string $relation_name
	 *  The name of the relation, or the name of 
	 *  a class, which extends Db_Row, representing the foreign table.
	 *  We use the relations set up, in $this->getSetUp(),
	 *  through the use of {@link Db_Row::hasOne()} and {@link Db_Row::hasMeny()}.
	 * @param string $fields
	 *  The fields to return
	 * @param array $inputs
	 *  An associative array of table_alias => DbRecord pairs.
	 *  If you think of foreign tables as parent nodes, then the relation
	 *  is a tree that has as its root, the table you want to return.
	 *  The relation determines the joins between the tables, but
	 *  you may want to specify the "input records" to limit the results
	 *  using the WHERE clause. Here is an example:
	 * <code>
	 *  // Assume the relation "Tags" had specified the following tree:
	 *  // user_item_tag
	 *  // |
	 *  // - user
	 *  // - item
	 * 
	 *  // Return all the tags this $user has placed on this $item.
	 *   $user->getRelated('tags', array('i' => $item));
	 *  // Return all the tags this $user has placed on all items.
	 *  // Note, however, that only the Tag record portion is returned,
	 *  // even though the Items table is still joined onto it.
	 *   $user->getRelated('tags');
	 * </code>
	 * @param boolean $modify_query
	 *  If true, returns a Db_Query object that can be modified, rather than
	 *  the result. You can call more methods, like limit, offset, where, orderBy,
	 *  and so forth, on that Db_Query. After you have modified it sufficiently,
	 *  get the ultimate result of this function, by calling the resume() method on 
	 *  the Db_Query object (via the chainable interface).
	 * @param array $options
	 *  Array of options to pass to beforeGetRelated and beforeGetRelatedExecute
	 *  functions.
	 * @return array|Db_Row|false
	 *  If the relation was defined with hasMany, returns an array of db rows.
	 *  If the relation was defined with hasOne, returns a db row or false. 
	 */
	function getRelated (
		$relation_name, 
		$fields = array(),
		$inputs = array(),
		$modify_query = false,
		$options = array())
	{
		if (empty($inputs))
			$inputs = array();
		
		if (empty($fields))
			$fields = array();
		
		$mySetUp = & $this->getSetUp();
		if (! isset($mySetUp['relations'][$relation_name])) {
			throw new Exception("Relation $relation_name not found.");
		}
		if (! isset($mySetUp['relations_many'][$relation_name])) {
			throw new Exception(
				"Information on relation $relation_name not found."
			);
		}
		
		$callback = array($this, "beforeGetRelated");
		if (is_callable($callback)) {
			$result = call_user_func($callback, $relation_name, $fields, $inputs);
		}
		if (isset($result))
			return $result;
		
//		$inputs_string = '';
//		foreach ($inputs as $key => $value) {
//			$inputs_string .= $key;
//			foreach ($value as $v)
//				$inputs_string .= $v;
//		}
		
		$mySetUp = & $this->getSetUp();
		$relation = $mySetUp['relations'][$relation_name];
		//$many = $mySetUp['relations_many'][$relation_name];
		$class_name = $mySetUp['relations_class_name'][$relation_name];
		$alias = $mySetUp['relations_alias'][$relation_name];
		$root_table = $relation->getRootTable();
		
		$inputs[$alias] = $this; // This object should always be one of the inputs

		$db = $this->getDb();
		if (empty($db))
			throw new Exception("The database was not specified!");
		
		$pieces = explode(' ', $root_table);
		//$table2 = $pieces[0];
		$has_alias = (count($pieces) > 1) and end($pieces);
		$alias2 = ! empty($has_alias) ? end($pieces) : reset($pieces);
		
		// Try to be accomodating:
		if (is_string($fields))
			$fields = array($alias2 => $fields);
		
		//$root_table_fields_prefix = null;
		if (! isset($fields[$alias2])) {
			if (class_exists($class_name)) {
				$o = new $class_name();
				if (method_exists($o, 'fieldNames')) {
					$table_fields = $o->fieldNames($alias2, $alias2 . '_');
					$fields[$alias2] = $table_fields;
					//$root_table_fields_prefix = $alias2 . '_';
				} else {
					$fields[$alias2] = "$alias2.*";
				}
			}
		}
		
		if (empty($fields[$alias2]))
			$fields[$alias2] = '*';
			
		$query = $db->select($fields[$alias2], $root_table);
		
		//static $alias_counter = 0;
		for ($i=1; $i < 100; ++$i) {
			$level = $relation->getLevel($i);
			if (empty($level))
				break;
			foreach ($level as $r) {
				foreach ($inputs as $alias => $object) {
					$table = $object->getTable();
					$key = ($table == $alias) ? $table : "$table $alias";
					if ($key == $r->table
					or "$table AS $alias" == $r->table
					or "$table as $alias" == $r->table) {
						continue 2; // do not join inputted tables
					}
				}
				$query = $query->join($r->table, $r->foreign_key, $r->join_type);

				$pieces = explode(' ', $r->table);
				$table3 = $pieces[0];
				$has_alias = (count($pieces) > 1) and end($pieces);
				$alias3 = ! empty($has_alias) ? end($pieces) : $pieces[0];
				$connection_name = $db->connectionName();
				$table_class_name = ucfirst($connection_name.'_')
					. Db::generateTableClassName($table3);
				if (! empty($class_name) and $class_name != 'Db_Row') {
					// Don't add any more fields from non-root tables.
				} else if (is_array($fields) and isset(
					$fields[$alias3])) {
					$query = $query->select($fields[$alias3], null);
				} else {
					if (class_exists($table_class_name)) {
						$o = new $table_class_name();
						if (method_exists($o, 'fieldNames')) {
							$table_fields = $o->fieldNames($alias3, 
								$alias3 . '_');
							$query = $query->select($table_fields, null);
						} else {
							$query = $query->select("$alias3.*", null);
						}
					} else {
						$query = $query->select("$alias3.*", null);
					}
				}
			}
		}
		$relations = $relation->getRelations();
		
		// Fill out the where clause
		foreach ($inputs as $alias => $row) {
			$table = $row->getTable();
			$key = "$table $alias";
			if (! isset($relations[$key])) {
				// try another variation
				$key = "$table AS $alias";
				if (! isset($relations[$key])) {
					// try yet another variation
					$key = "$table as $alias";
					if (! isset($relations[$key]))
						throw new Exception("No table corresponding to '$table $alias'", - 1);
				}
			}
			$r = $relations[$key];
			$where_fields = array_flip($r->foreign_key);
			foreach ($where_fields as $key => &$value) {
				$pieces = explode('.', $value, 2);
				$field_name = isset($pieces[1]) ? $pieces[1] : $pieces[0];
				$value = $row->$field_name;
			}
			$query = $query->where($where_fields);
		}
		
		// Set the class to be returned
		$query->className = $mySetUp['relations_class_name'][$relation_name];
		
		// Perhaps the extending class wants to do something else with this query
		// before it is executed
		$callback = array($this, "beforeGetRelatedExecute", $options);
		if (is_callable($callback))
			$query = call_user_func($callback, $relation_name, $query);
		if (empty($query))
			return false;
			
		// Gather all the arguments together for getRelated_resume() method
		$resume_args = array(
			$relation_name, $field_name, $inputs,
			$modify_query, $options
		);
		$resume_args[] = compact(
			'many', 'options',
			'class_name', 'root_table_fields_prefix'
		);
		
		// Modify the query if necessary
		if ($modify_query) {
			$query->setContext(array($this, 'getRelated'), $resume_args);
			return $query;
		}

		// Return the result
		$resume_args[] = $query;
		return call_user_func_array(array($this, 'getRelated_resume'), $resume_args);
	}
	
	function getRelated_resume (
		$relation_name, 
		$fields = array(),
		$inputs = array(),
		$modify_query = false,
		$options = array(),
		$preserved_vars = array(),
		$query = null)
	{
		// Resumes getRelated() function, possibly after
		// the intermediate query executes.
		extract($preserved_vars);

		/** @var $class_name */
		/** @var $root_table_fields_prefix */
		/** @var $many */
		if (isset($class_name) and $class_name != 'Db_Row') {
			
			$rows_array = $query->fetchAll(PDO::FETCH_ASSOC);
			$rows = array();
			foreach ($rows_array as $row_array) {
				$row = new $class_name();
				$row->retrieved = true;
				$row->copyFrom($row_array, $root_table_fields_prefix);
				foreach ($row->fields_modified as $key => $value)
					$row->fields_modified[$key] = false;
				$pk = array();
				foreach ($row->getPrimaryKey() as $field) {
					$pk[$field] = $row_array[$field];
				}
				// FIXME
				/** @var $row Db_Row */
				$row->setPkValue($pk);
				$rows[] = $row;
			}
		} else {
			$rows = $query->fetchDbRows();
		}
		
		if ($many) {
			$return = $rows;
		} else {
			$return = count($rows) > 0 ? $rows[0] : false;
		}
			
		//$mySetUp['relations_cache'][$hash] = $return;

		return $return;
	}

	/**
	 * Gets the value of a field
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 * @param mixed $default
	 *  The last parameter should not be omitted,
	 *  and contains the default value to return in case
	 *  the requested field was not indicated.
	 */
	function get(
	 $key1,
	 $default)
	{
		$args = func_get_args();
		if (!isset($this->p)) {
			$this->p = new Pie_Parameters;
		}
		return call_user_func_array(array($this->p, __FUNCTION__), $args);
	}
	
	/**
	 * Sets the value of a field
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 * @param mixed $value
	 *  The last parameter should not be omitted,
	 *  and contains the value to set the field to.
	 */
	function set(
	 $key1,
	 $value)
	{
		$args = func_get_args();
		if (!isset($this->p)) {
			$this->p = new Pie_Parameters;
		}
		return call_user_func_array(array($this->p, __FUNCTION__), $args);
	}

	/**
	 * Clears the value of a field, possibly deep inside the array
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 */
	function clear(
	 $key1,
	 $value)
	{
		$args = func_get_args();
		if (!isset($this->p)) {
			$this->p = new Pie_Parameters;
		}
		return call_user_func_array(array($this->p, __FUNCTION__), $args);
	}

	function getAll()
	{
		$args = func_get_args();
		if (!isset($this->p)) {
			$this->p = new Pie_Parameters;
		}
		return call_user_func_array(array($this->p, __FUNCTION__), $args);
	}

	/**
	 * Extra shortcuts when calling methods
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	function __call ($name, $args)
	{
		// Default implementations of 
		// get
		// set
		// clear
		// getAll
		// beforeSet_$name,
		// afterSet_$name,
		// beforeSet_$name
		// afterSet,
		// isset_$name
		// unset_$name
		// beforeGetRelated
		// beforeGetRelatedExecute
		// beforeRetrieve
		// beforeSave
		// beforeSaveExecute
		// afterSaveExecute
		switch ($name) {
			case 'get':
			case 'set':
			case 'clear':
			case 'getAll':
				if (! isset($this->p))
					$this->p = new Pie_Parameters();
				return call_user_func_array(array($this->p, $name), $args);
			case 'beforeGetRelated':
				return null;
			case 'beforeGetRelatedExecute':
				return $args[1];
			case 'beforeRetrieve':
				return $args[0];
			case 'beforeSave':
				return $args[0];
			case 'beforeSaveExecute':
				return $args[0];
			case 'afterSaveExecute':
				return $args[0];
			case 'beforeDelete':
				return true;
			case 'beforeDeleteExecute':
				return $args[0];
			case 'afterDeleteExecute':
				return $args[0];
			case 'afterSet':
				return true;
		}
		
		$pieces = explode('_', $name, 2);
		
		if ($pieces[0] == 'beforeSet')
			return array($pieces[1], $args[0]);
		if ($pieces[0] == 'afterSet')
			return $args[0];
		if ($pieces[0] == 'beforeGet') {
			$field_name = $pieces[1];
			if (array_key_exists($field_name, $this->fields)) {
				return $this->fields[$field_name];
			} else {
				$get_class = get_class($this);
				$backtrace = debug_backtrace();
				$function = $line = $class = null;
				if (isset($backtrace[4]['function'])) {
					$function = $backtrace[4]['function'];
				}
				if (isset($backtrace[4]['line'])) {
					$line = $backtrace[4]['line'];
				}
				if (isset($backtrace[4]['class'])) {
					$class = $backtrace[4]['class'];
				}
				throw new Exception(
					"$get_class does not have $field_name field set, "
					. "called in $class::$function (line $line in function)."
				);
				return null;
			}
		}
		if ($pieces[0] == 'isset') {
			return isset($this->fields[$pieces[1]]);
		} else if ($pieces[0] == 'unset') {
			unset($this->fields[$pieces[1]]);
			return;
		} else if ($pieces[0] == 'get') {
			$relation_name = $pieces[1];

			if (isset($args[4])) {
				return $this->getRelated($relation_name, $args[0], $args[1], 
					$args[2], $args[3], $args[4]);			
			}
			if (isset($args[3])) {
				return $this->getRelated($relation_name, $args[0], $args[1], 
					$args[2], $args[3]);			
			}
			if (isset($args[2])) {
				return $this->getRelated($relation_name, $args[0], $args[1], 
					$args[2]);
			}
			if (isset($args[1])) {
				return $this->getRelated($relation_name, $args[0], $args[1]);
			}
			if (isset($args[0])) {
				return $this->getRelated($relation_name, $args[0]);
			}
			return $this->getRelated($relation_name, array());
		}
		
		$class_name = get_class($this);
		throw new Exception("calling method {$class_name}->{$name}, which doesn't exist");
		
		// otherwise, function doesn't exist.
		return false;
	}
	
	/**
	 * Gets an row or array of rows from a source and a relation
	 * @param DbRow|array $source
	 *  Can be a object extending Db_Row, 
	 *  or it can be an array of such objects, which must all be of the same class.
	 * @param string $relation_name
	 *  The name of the relation to use in getRelated.
	 * @return DbRow|array
	 *  If $source is a single row and the relation was declared with hasOne,
	 *  then a single row is returned. Otherwise, an associative array is returned.
	 *  The keys of the associative array are the serialized keys of the
	 *  rows. The values are themselves either rows, or arrays of rows,
	 *  depending on whether the relation was declared with hasOne or hasMany.
	 */
	static function from(
	 $source,
	 $relation_name)
	{
		// TODO: implement
	}

	/**
	 * Deletes the rows in the database
	 * @param string|array $search_criteria
	 *  You can provide custom search criteria here, such as array("tag.name LIKE " => $this->name)
	 *  If this is left null, and this Db_Row was retrieved, then the db rows corresponding
	 *  to the primary key are deleted.
	 *  But if it wasn't retrieved, then the modified fields are used as the search criteria.
	 * @param boolean $use_primary_key
	 *  If true, the primary key is used in searching for rows to delete. 
	 *  An exception is thrown when some fields of the primary key are not specified
	 * @return int
	 *  Returns number of rows deleted
	 */
	function remove ($search_criteria = null, $use_primary_key = false)
	{
		$class_name = get_class($this);

		// Check if we have specified all the primary key fields,
		if (!empty($use_primary_key)) {
			$primaryKey = $this->getPrimaryKey();
			$primaryKeyValue = $this->calculatePKValue();
			if (!is_array($primaryKeyValue)) {
				throw new Exception("No fields of the primary key were specified for $class_name.");
			}
			if (is_array($primaryKey)) {
				foreach ($primaryKey as $field_name)
					if (! array_key_exists($field_name, $primaryKeyValue))
						throw new Exception(
							"Primary key field $field_name was not specified for $class_name.");
				foreach ($primaryKeyValue as $field_name => $value)
					if (! in_array($field_name, $primaryKey))
						throw new Exception(
							"The field $field_name is not part of the primary key for $class_name.");
			}
			
			// If search criteria are not specified, try to compute them.
			$use_search_criteria = isset($search_criteria) 
				? $search_criteria 
				: $this->calculatePKValue();
		} else {
			$modified_fields = array();
			foreach ($this->fields as $name => $value)
				if ($this->fields_modified[$name])
					$modified_fields[$name] = $value;
			
			// If search criteria are not specified, use the modified fields instead.
			$use_search_criteria = isset($search_criteria) 
				? $search_criteria 
				: $modified_fields;
		}
		
		// If search criteria are not specified, try to compute them.
		if (isset($search_criteria)) {
			$use_search_criteria = $search_criteria;
		} else {
			$search_criteria = $this->calculatePKValue();
			if (empty($search_criteria)) {
				$modified_fields = array();
				foreach ($this->fields as $name => $value)
					if ($this->fields_modified[$name])
						$modified_fields[$name] = $value;
				$search_criteria = $modified_fields;
			}
		}
		
		$callback = array($this, "beforeDelete");
		if (is_callable($callback))
			$continue_deleting = call_user_func($callback, $search_criteria);
		if (! isset($continue_deleting) or $continue_deleting === false)
			return false;
		if (! is_bool($continue_deleting)) {
			throw new Exception(
				__CLASS__."::beforeDelete() must return a boolean - whether to delete or not!", 
				-1000);
		}

		$db = $this->getDb();
		if (empty($db))
			throw new Exception("The database was not specified!");
		$table = $this->getTable();
		$query = $db->delete($table)->where($search_criteria);
		
		$callback = array($this, "beforeDeleteExecute");
		if (is_callable($callback))
			$query = call_user_func($callback, $query);

		/** @var $result Db_Result */
		// Now, execute the query!
		if (! empty($query) and $query instanceof iDb_Query) {
			/** @var $query Db_Query_Mysql */
			$result = $query->execute();
		}
		
		$callback = array($this, "afterDeleteExecute");
		if (is_callable($callback))
			$result = call_user_func($callback, $result);

		return $result->rowCount();
	}

	/**
	 * Saves the row in the database.
	 * 
	 * If the row was retrieved from the database, issues an UPDATE.
	 * If the row was created from scratch, then issue an INSERT.
	 *
	 * @param boolean $on_duplicate_key_update
	 *  If MySQL is being used, you can set this to TRUE
	 *  to add an ON DUPLICATE KEY UPDATE clause to the INSERT statement
	 * 
	 * @return boolean|Db_Query
	 *  If successful, returns the Db_Query that was executed.
	 *  Otherwise, returns false.
	 */
	function save ($on_duplicate_key_update = false)
	{
		$this_class = get_class($this);
		if ($this_class == 'Db_Row')
			throw new Exception(
				"If you're going to save, please extend Db_Row.");
		
		$modified_fields = array();
		foreach ($this->fields as $name => $value)
			if ($this->fields_modified[$name])
				$modified_fields[$name] = $value;
		
		$callback = array($this, "beforeSave");
		if (is_callable($callback))
			$modified_fields = call_user_func($callback, $modified_fields);
		if (! isset($modified_fields) or $modified_fields === false)
			return false;
		if (! is_array($modified_fields))
			throw new Exception(
				"$this_class::beforeSave() must return the array of (modified) fields to save!", 
			-1000);
		
		$db = $this->getDb();
		if (empty($db))
			throw new Exception("The database was not specified!");
		$table = $this->getTable();
		if ($this->retrieved) {
			// Do an update of an existing row
			//if (count($modified_fields) > 0) {
			$pk = $this->getPkValue();
			// If pkValue cantains more or less fields than
			// the primary key should, it is only through tinkering.
			// We'll let it pass, since the person was most likely
			// trying to do something clever.
			if (empty($modified_fields))
				return false;
			$query = $db->update($table)
				->set($modified_fields)
				->where($pk);
			//}
			$inserting = false;
		} else {
			// Do an insert
			//if (count($modified_fields) == 0)
			//    throw new Exception("No fields have been set. Nothing to save!");
			$query = $db->insert($table, $modified_fields);
			if ($on_duplicate_key_update) {
				$on_duplicate_key_update_fields = $modified_fields;
				$pk = $this->getPrimaryKey();
				if (count($pk) == 1) {
					$field_name = reset($pk);
					$on_duplicate_key_update_fields = array_merge(
						array($field_name => new Db_Expression("LAST_INSERT_ID($field_name)")),
						$on_duplicate_key_update_fields
					);
				}
				$query = $query->onDuplicateKeyUpdate($on_duplicate_key_update_fields);
			}
			$inserting = true;
		}
		
		$callback = array($this, "beforeSaveExecute");
		if (is_callable($callback))
			$query = call_user_func($callback, $query, $modified_fields);
			
		// Now, execute the query!
		if (! empty($query) and $query instanceof iDb_Query) {
			$result = $query->execute();
			if ($inserting) {
				$this->retrieved = true; // Now treat as retrieved
				
				// If this was an insert with a single autoincrement field,
				// the autoincrement field should have been the PK value, so store it.
				$pk = $this->getPrimaryKey();
				if ($new_id = $db->lastInsertId()) {
					if (count($pk) == 1) {
						$field_name = reset($pk);
						$this->$field_name = $new_id;
					}
				}
				
				// Save however many fields we can into the primary key value.
				// Next time, this record will be updated.
				foreach ($pk as $field_name) {
					if (isset($this->fields[$field_name])) {
						$this->pkValue[$field_name] = $this->fields[$field_name];
					}
				}
				// TODO: Handle the case where one of the fields in the PK
				// is an autoincrement field, and the others are not!
			}
		}
		
		$callback = array($this, "afterSaveExecute");
		if (is_callable($callback))
			$result = call_user_func($callback, $result, $query, $modified_fields);
			
		// Finally, set all fields as unmodified again
		if (is_array($this->fields))
			foreach ($this->fields as $name => $value)
				$this->fields_modified[$name] = false;
		
		return $query;
	}

	/**
	 * Retrieves the row in the database
	 * @param string $fields
	 *  The fields to retrieve and set in the Db_Row.
	 *  This gets used if we make a query to the database.
	 * @param boolean $use_primary_key
	 *  If true, the primary key is used in searching. 
	 *  An exception is thrown when some fields of the primary key are not specified
	 * @param boolean $modify_query
	 *  If true, returns a Db_Query object that can be modified, rather than
	 *  the result. You can call more methods, like limit, offset, where, orderBy,
	 *  and so forth, on that Db_Query. After you have modified it sufficiently,
	 *  get the ultimate result of this function, by calling the resume() method on 
	 *  the Db_Query object (via the chainable interface).
	 * @param array $options
	 *  Array of options to pass to beforeRetrieve and afterRetrieve functions.
	 * @return array|Db_Row
	 *  Returns the row or array of rows fetched from the Db_Result (or returned by beforeRetrieve)
	 *  If retrieve() is called with no arguments, may return false if nothing retrieved.
	 */
	function retrieve (
		$fields = '*', 
		$use_primary_key = false, 
		$modify_query = false,
		$options = array())
	{
		$search_criteria = null;
		$class_name = get_class($this);
		// Check if we have specified all the primary key fields.
		if (!empty($use_primary_key)) {
			$primaryKey = $this->getPrimaryKey();
			$primaryKeyValue = $this->calculatePKValue();
			if (!is_array($primaryKeyValue)) {
				throw new Exception("No fields of the primary key were specified for $class_name.");
			}
			if (is_array($primaryKey)) {
				foreach ($primaryKey as $field_name)
					if (! array_key_exists($field_name, $primaryKeyValue))
						throw new Exception(
							"Primary key field $field_name was not specified for $class_name.");
				foreach ($primaryKeyValue as $field_name => $value)
					if (! in_array($field_name, $primaryKey))
						throw new Exception(
							"The field $field_name is not part of the primary key for $class_name.");
			}
			
			// Use the primary key value as the search criteria
			$use_search_criteria = $primaryKeyValue;
		} else {
			$modified_fields = array();
			foreach ($this->fields as $name => $value)
				if ($this->fields_modified[$name])
					$modified_fields[$name] = $value;
			
			// Use the modified fields as the search criteria
			$use_search_criteria = array();
			$table = $this->getTable();
			foreach ($modified_fields as $key => $value)
				$use_search_criteria["$table.$key"] = $value;
				
			// If no fields were modified on this object,
			// then this function will just return an empty array -- see below.
		}
		
		$callback = array($this, "beforeRetrieve");
		if (is_callable($callback))
			$use_search_criteria = call_user_func($callback, $use_search_criteria, $options);
		
		// Now, get the results.
		if (empty($use_search_criteria)) {
			// it was set by the beforeRetrieve callback
			return $use_search_criteria; 
		} else if ($use_search_criteria instanceof Db_Row) {
			// it was set by the beforeRetrieve callback
			$rows = array($use_search_criteria); 
		} else if (is_array($use_search_criteria) 
		and isset($use_search_criteria[0]) 
		and ($use_search_criteria[0] instanceof Db_Row)) {
			$rows = $use_search_criteria;
		} else {
			$query = $this->getDb()
				->select($fields, $this->getTable())
				->where($use_search_criteria);
				
			// Gather all the arguments together for retrieve_resume() method
			$resume_args = array(
				$fields, $use_primary_key,
				$modify_query, $options
			);
			$resume_args[] = compact(
				'search_criteria', 'options'
			);

			// Modify the query if necessary
			if ($modify_query) {
				$query->setContext(array($this, 'retrieve'), $resume_args);
				return $query;
			}

			// Return the result
			$resume_args[] = $query;
			return call_user_func_array(array($this, 'retrieve_resume'), $resume_args);
		}
		
		if (isset($search_criteria))
			return $rows;
		// Return one db row, as per function description
		if (isset($rows[0])) {
			$this->copyFromRow($rows[0], '', true);
			return $this;
		} else {
			return false;
		}
	}

	function retrieve_resume (
		$fields = '*', 
		$use_primary_key = false, 
		$modify_query = false,
		$options = array(),
		$preserved_vars = array(),
		$query = null)
	{
		$query = $query->limit(1); // get at most one
		$rows = $query->fetchDbRows(get_class($this));
		
		// Return one db row, as per function description
		if (isset($rows[0])) {
			$this->copyFromRow($rows[0], '', true);
			return $this;
		} else {
			return false;
		}
	}

	/**
	 * Retrieves the row in the database, or if it doesn't exist, saves it.
	 * @param string|array $search_criteria
	 *  You can provide custom search criteria here, such as array("tag.name LIKE " => $this->name)
	 * @param string $fields
	 *  The fields to retrieve and set in the Db_Row.
	 *  This gets used if we make a query to the database.
	 * @param array $options
	 *  Array of options to pass to beforeRetrieve and afterRetrieve functions.
	 *
	 * @return boolean
	 *  returns whether the record was saved (i.e. false means retrieved)
	 */
	function retrieveOrSave (
		$search_criteria = null,
		$fields = '*', 
		$options = array())
	{
		$rows = $this->retrieve($search_criteria, true, $fields, false, $options);
		if (! empty($rows)) {
			if (is_array($rows))
				$this->copyFromRow($rows[0]);
			return false;
		}

		$this->save();
		return true;
	}

	/**
	 * This function copies the members of another row,
	 * as well as the primary key, etc. and assigns it to this row.
	 * @param Db_Row $row
	 *  The source row. Be careful -- In this case, Db does not check 
	 *  whether the class of the Db_Row matches. It leaves things up to you.
	 * @param string $strip_prefix
	 *  If not empty, only copies the elements with the prefix, stripping it out.
	 *  Useful for assigning parts of Db_Rows that came from joins, to individual table classes.
	 * @param bool $suppress_hooks
	 *  If true, assigns everything but does not fire the beforeSet and afterSet events.
	 */
	function copyFromRow (
		Db_Row $row, 
		$strip_prefix = null, 
		$suppress_hooks = false)
	{
		$this->retrieved = $row->retrieved;
		if (!empty($strip_prefix)) {
			$prefix_len = strlen($strip_prefix);
			$this->pkValue = isset($row->pkValue) ? Db_Utils::take(
				$row->pkValue, array(), $strip_prefix) : array();
		} else {
			$this->pkValue = $row->pkValue;
		}
		
		foreach ($row->fields as $key => $value) {
			if (!empty($strip_prefix)) {
				if (strncmp($key, $strip_prefix, $prefix_len) != 0)
					continue;
				$stripped_key = substr($key, $prefix_len);
			} else {
				$stripped_key = $key;
			}
			if ($suppress_hooks) {
				$this->fields[$stripped_key] = $value;
			} else {
				$this->$stripped_key = $value;
			}
			$this->fields_modified[$stripped_key] = isset($row->fields_modified[$key])
				? $row->fields_modified[$key] : false;
		}
	}

	/**
	 * This function copies the members of an array or something supporting "Enumerable".
	 * @param mixed $source
	 *  The source of the parameters. Typically the output of Db_Utils::take, unleashed
	 *  on $_POST or $_REQUEST or something like that. Just used for convenience.
	 * @param string $strip_prefix
	 *  If not empty, only copies the elements with the prefix, stripping it out.
	 *  Useful for assigning values whose names were prefixed with namespaces.
	 * @param bool $suppress_hooks
	 *  If true, assigns everything but does not fire the beforeSet and afterSet events.
	 * @param bool $mark_modified
	 *  Defaults to false. Whether to mark the affected fields as modified or not.
	 */
	function copyFrom (
		$source, 
		$strip_prefix = null, 
		$suppress_hooks = false, 
		$mark_modified = true)
	{
		if ($source instanceof Db_Row)
			return $this->copyFromRow($source, false, $strip_prefix, $suppress_hooks);
			
		if (!empty($strip_prefix)) {
			$prefix_len = strlen($strip_prefix);
		}
		
		foreach ($source as $key => $value) {
			if (!empty($strip_prefix)) {
				if (strncmp($key, $strip_prefix, $prefix_len) != 0)
					continue;
				$stripped_key = substr($key, $prefix_len);
			} else {
				$stripped_key = $key;
			}
			if ($suppress_hooks) {
				$this->fields[$stripped_key] = $value;
			} else {
				$this->$stripped_key = $value;
			}
			if ($mark_modified) {
				$this->fields_modified[$stripped_key] = true;
			} else {
				$this->fields_modified[$stripped_key] = false;
			}
		}
	}

	/**
	 * Returns an array of fields representing this row
	 * @param array $options
	 *  An array of options. Currently not used.
	 */
	function toArray($options = null)
	{
		return $this->fields;
	}

	/**
	 * Implements __set_state method, so it can be exported
	 */
	public static function __set_state(array $array) {
		$result = new Db_Row();
		foreach($array as $k => $v)
			$result->$k = $v;
		return $result;
	}

	/**
	 * Iterator implementation - rewind
	 */
	function rewind ()
	{
		if (! empty($this->fields))
			$this->beyondLastField = false; else
			$this->beyondLastField = true;
		return reset($this->fields);
	}

	function valid ()
	{
		return ! $this->beyondLastField;
	}

	function current ()
	{
		return current($this->fields);
	}

	function key ()
	{
		return key($this->fields);
	}

	function next ()
	{
		$next = next($this->fields);
		$key = key($this->fields);
		if (isset($key)) {
			return $next;
		} else {
			$this->beyondLastField = true;
			return false; // doesn't matter what we return here, see valid()
		}
	}

	/**
	 * Dumps the result as an HTML table. 
	 * @return string
	 */
	function __toMarkup ()
	{
		try {
			$return = "<table class='dbRowTable'>\n";
			$return .= "<tr>\n";
			$return .= "<td class='key'>Field name</td>\n";
			$return .= "<td class='value'>Field value</td>\n";
			$return .= "<td class='modified'>Field modified?</td>\n";
			$return .= "</tr>\n";
			foreach ($this->fields as $key => $value) {
				$return .= "<tr>\n";
				$return .= "<td class='key'>" . htmlentities($key) . '</td>' .
					 "\n";
				$return .= "<td class='value'>" . htmlentities($value) . '</td>' .
					 "\n";
				$return .= "<td class='modified'>"
					. ($this->wasModified($key) 
					 ? 'Yes' 
					 : 'No')
					. '</td>' . "\n";
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
	 */
	function __toString ()
	{
		try {
			$ob = new Pie_OutputBuffer();
			$results = array();
			foreach ($this->fields as $key => $value) {
				$results[] = array(
					'Field name:' => $key, 
					'Field value:' => $value, 
					'Field modified:' => $this->wasModified($key) ? 'Yes' : 'No'
				);
			}
			Db_Utils::dump_table($results);
			return $ob->getClean();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	function __sleep ()
	{
		return array_keys(get_object_vars($this));
	}
	
	function __wakeup()
	{
		$this->init();
	}
	
	protected $beyondLastField = false;
}
