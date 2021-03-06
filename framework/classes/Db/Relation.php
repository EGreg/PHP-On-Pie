<?php

/**
 * This class lets you use relationships between Db tables.
 * @package Db
 */

class Db_Relation
{	
	public $table;
	public $foreign_key;
	public $foreign_table;
	public $join_type;
	
	protected $root_table;
	
	/**
	 * The sub-relations comprising this relation
	 * @var array()
	 */
	protected $relations;
	
	/**
	 * Whether the relation has been compiled yet.
	 * @var boolean
	 */
	protected $was_compiled;

	/**
	 * Constructor
	 *
	 * @param array $relations
	 *  Pass an array of Db_Relations and they will be used together
	 *  to build up an aggregate Db_Relation.
	 *  
	 * 
	 * OR
	 * 
	 * 
	 *  Pass 
	 *  (
	 *  string $table_name, 
	 *  array $foreign_key, 
	 *  string $foreign_table
	 *  string $join_type = 'LEFT'
	 *  )
	 *  to create a Db_Relation between these two tables.
	 *  Every $foreign_key array is an
	 *  associative array of local => foreign field names,
	 *  for example
	 *  <code>
	 *   array('user.id' => 't.user_id', 'i.item_id' => 't.item_id')
	 *  </code>
	 */
	function __construct (
		$table, 
		$foreign_key = null, 
		$foreign_table = null, 
		$join_type = 'LEFT')
	{
		$this->relations = array();
		
		if (is_array($table)) {
			$relations = $table;
			
			$relations_assoc = array();
			foreach ($relations as $i => $r)
				$relations_assoc[$r->table] = $r;
			$relations = $relations_assoc;
			
			$this->relations = $relations;
			
			foreach ($relations as $relation) {
				$this->relations = array_merge(
					$this->relations, 
					$relation->relations
				);
			}
		} else {
			$this->table = $table;
			$this->foreign_key = $foreign_key;
			$this->foreign_table = $foreign_table;
			$this->join_type = $join_type;
			$this->root_table = $foreign_table;
		}
	}
	
	/**
	 * Before being used, a relation has to be compiled.
	 * Compiling finds the root table and levels from the root table,
	 * so joins can be constructed from the relation.
	 */
	function compile()
	{	
		// Make sure there is only one root, and label it.
		$foreign_tables = array();
		$tables = array();
		foreach ($this->relations as $relation) {
			$foreign_tables[$relation->foreign_table] = true;
			$tables[$relation->table] = true;
		}
		$root_tables = array_diff_key($foreign_tables, $tables);
		$count = count($root_tables);
		if ($count > 1) {
			$message = 'There can only be one root table in a relation! We have more: ' .
				 implode(', ', array_keys($root_tables));
			throw new Exception($message, - 1);
		} else if ($count == 0) {
			throw new Exception(
				'There must be exactly one root table in a relation! We have none (circular relation?)'
			);
		}
		
		reset($root_tables);
		$this->root_table = key($root_tables);
		
		// Build the levels from the root
		$leveled_tables = array($this->root_table => true);
		$leveled_tables_frozen = $leveled_tables;
		for ($i = 1; $i < 100; ++$i) {
			$level = array();
			$l_count = 0;
			foreach ($this->relations as $relation) {
				if (array_key_exists($relation->foreign_table, $leveled_tables_frozen)
				and !isset($leveled_tables[$relation->table])) {
					$level[] = $relation;
					$leveled_tables[$relation->table] = true;
					++$l_count;
				}
			}
			$this->levels[$i] = $level;
			if ($l_count == 0)
				break;
			$leveled_tables_frozen = $leveled_tables;
		}
		$this->was_compiled = true;
	}

	/**
	 * Returns the root table name, i.e. the unique foreign table that has
	 * no foreign key coming out of it.
	 *
	 * @return string
	 */
	function getRootTable ()
	{
		if (!$this->was_compiled) {
			$this->compile();
		}
		return $this->root_table;
	}

	/**
	 * Returns the array of Db_Relations this Db_Relation consists of.
	 * If null or empty, that means this Db_Relation is not a container.
	 *
	 * @return array
	 */
	function getRelations ()
	{
		return $this->relations;
	}
	
	/**
	 * Returns a level of relations, from 1 to however many.
	 * Level 0 is the root table of the relation.
	 * The next level is all tables that have a foreign key to the root table.
	 * And so forth.
	 * This is the order they should appear in JOIN statements,
	 * for the statements to make sense.
	 */
	function getLevel($index)
	{
		if (!$this->was_compiled) {
			$this->compile();
		}
		if ($index <= 0) 
			return false;
		return $this->levels[$index];
	}
	
	// Level 0 is the root table
	// This array contains the other levels... 1 through n
	// where a table is on level k if there is a foreign key
	// from it to a table on level k-1, and not to any other tables
	// on levels below k-1.
	public $levels = array();
}
;
