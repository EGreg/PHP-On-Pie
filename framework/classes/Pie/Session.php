<?php

/**
 * Session-related functionality
 * @package Pie
 */

class Pie_Session
{
	static protected $session_save_path;
	static protected $session_db_connection;
	static protected $session_db_table;
	static protected $session_db_data_field;
	static protected $session_db_id_field;
	static protected $session_db_updated_field;
	static protected $session_db;
	static protected $session_db_row;
	static protected $session_db_row_class;
	
	static function name($name = null)
	{
		if ($name2 = Pie::event('pie/session/name', compact('name'), 'before')) {
			return $name2;
		}
		if (isset($name)) {
			return session_name($name);
		}
		return session_name();
	}
	
	static function id ($id = null)
	{
		if ($id2 = Pie::event('pie/session/id', compact('id'), 'before')) {
			return $id2;
		}
		if (isset($id)) {
			return session_id($id);
		}
		return session_id();
	}
	
	static function savePath ($savePath = null)
	{
		if ($savePath2 = Pie::event('pie/session/savePath', compact('savePath'), 'before')) {
			return $savePath2;
		}
		if (isset($savePath)) {
			return session_save_path($savePath);
		}
		$sp = session_save_path();
		// A workaround for some systems:
		if (empty($sp)) {
			$sp = PIE_FILES_DIR.DS.'sessions';
			session_save_path($sp);
		}
		return $sp;
	}
	
	static function init()
	{
		if (Pie_Config::get('pie', 'session', 'appendSuffix', false)
		or isset($_GET[self::name()])) {
			if (self::id()) {
				$s = '?'.self::name().'='.self::id();
				$suffix = Pie_Uri::suffix();
				$base_url = Pie_Request::baseUrl();
				$suffix[$base_url] = isset($suffix[$base_url])
					? $suffix[$base_url].$s 
					: $s;
				Pie_Uri::suffix($suffix);
			}
		}
		self::$inited = true;
	}
	
	static function start()
	{
		if (self::id()) {
			// Session has already started
			return false;
		}
		if (false === Pie::event('pie/session/start', array(), 'before')) {
			return false;
		}
		session_set_save_handler(
			array(__CLASS__, 'open'), 
			array(__CLASS__, 'close'), 
			array(__CLASS__, 'read'), 
			array(__CLASS__, 'write'), 
			array(__CLASS__, 'destroy'),
			array(__CLASS__, 'gc')
		);
		if (!empty($_SESSION)) {
			$pre_SESSION = $_SESSION;
		}
		session_start();
		self::init();
		// merge in all the stuff that was added to $_SESSION
		// before we started it.
		if (isset($pre_SESSION)) {
			foreach ($pre_SESSION as $k => $v) {
				$_SESSION[$k] = $v;
			}
		}
		Pie::event('pie/session/start', array(), 'after');
		return true;
	}
	
	/**
	 * You can call this function to clear out the contents of 
	 * a session, but keep its ID.
	 */
	static function clear()
	{
		$_SESSION = array();
		//foreach ($_SESSION as $k => $v) {
		//	unset($_SESSION[$k]);
		//}
	}
	
	/**
	 * You might want to use this instead of simply calling
	 * session_regenerate_id().
	 * Clones session with the given $session_id
	 * by generating a new session id.
	 * Writes to and closes the session with given $session_id.
	 * From now on, $_SESSION will be saved under the newly generated id.
	 * @param $destroy_old_session
	 *  Defaults to false. Set to true if you want to get rid
	 *  of the old session (to save space or for security purposes).
	 * @return string
	 *  The new session id.
	 * @author Gregory
	 */
	static function regenerate_id($destroy_old_session = false)
	{
		//  start a new session; this copies the $_SESSION data over
		session_regenerate_id($destroy_old_session);

		//  hang on to the new session id
		$sid = self::id();

		//  close the old and new sessions
		session_write_close();

		// we have to re-set all the handlers, due to a bug in PHP 5.2
		session_set_save_handler(
			array(__CLASS__, 'open'), 
			array(__CLASS__, 'close'), 
			array(__CLASS__, 'read'), 
			array(__CLASS__, 'write'), 
			array(__CLASS__, 'destroy'),
			array(__CLASS__, 'gc')
		);

		if ($sid) {
			//  re-open the session
			self::id($sid);
		}
		
		// start session
		session_start();
		
		return $sid;
	}
	
	//
	// Session handling functions
	//
	
	static function open ($save_path, $session_name)
	{
		$session_db_connection = Pie_Config::get('pie', 'session', 'dbConnection', null);
		if (! empty($session_db_connection)) {
			
			// use the DB for session
			$session_db_table = Pie_Config::get('pie', 'session', 'dbTable', null);
			if (empty($session_db_table)) {
				throw new Pie_Exception_WrongType(array(
					'field' => 'session_db_table', 
					'type' => 'string'
				));
			}
			$session_db_data_field = Pie_Config::get('pie', 'session', 'dbDataField', null);
			if (empty($session_db_table)) {
				throw new Pie_Exception_WrongType(array(
					'field' => 'session_db_data_field', 
					'type' => 'string'
				));
			}
			$session_db_id_field = Pie_Config::get('pie', 'session', 'dbIdField', null);
			if (empty($session_db_id_field)) {
				throw new Pie_Exception_WrongType(array(
					'field' => 'session_db_id_field', 
					'type' => 'string'
				));
			}
			$session_db_updated_field = Pie_Config::get('pie', 'session', 'dbUpdatedField', null);
			if (empty($session_db_updated_field)) {
				throw new Pie_Exception_WrongType(array(
					'field' => 'session_db_updated_field', 
					'type' => 'string'
				));
			}
			$session_db_row_class = Pie_Config::get('pie', 'session', 'dbRowClass', null);
			if (empty($session_db_row_class)
			or ! class_exists($session_db_row_class)) {
				throw new Pie_Exception_WrongType(array(
					'field' => 'session_db_row_class', 
					'type' => 'a class name'
				));
			}
			$class = $session_db_row_class;
			$ancestors = array($class);
			while ($class = get_parent_class($class))
				$ancestors[] = $class;
			if (! in_array('Db_Row', $ancestors)) {
				throw new Pie_Exception_WrongType(array(
					'field' => 'session_db_row_class', 
					'type' => 'name of a class that extends Db_Row'
				));
			}
			
			self::$session_db_connection = $session_db_connection;
			self::$session_db_table = $session_db_table;
			self::$session_db_data_field = $session_db_data_field;
			self::$session_db_id_field = $session_db_id_field;
			self::$session_db_updated_field = $session_db_updated_field;
			self::$session_db_row_class = $session_db_row_class;
			self::$session_db = Db::connect(self::$session_db_connection);
		}
		
		self::$session_save_path = $save_path;
		return true;
	}

	static function close ()
	{
		// perform garbage collection
		return self::gc(ini_get('session.gc_maxlifetime'));
	}

	static function read ($id)
	{
		if (!isset(self::$session_save_path)) {
			self::$session_save_path = self::savePath();
		}
		if (! empty(self::$session_db_connection)) {
			self::gc(ini_get('session.gc_maxlifetime'));
			
			$session_db_id_field = self::$session_db_id_field;
			$session_db_data_field = self::$session_db_data_field;
			$rows = self::$session_db
				->select('*', self::$session_db_table)
				->where(array(self::$session_db_id_field => $id))
				->limit(1)
				->fetchDbRows(self::$session_db_row_class);
			if (count($rows) > 0) {
				// Store the row from session table, to update later
				self::$session_db_row = $rows[0];
				return (string) self::$session_db_row->$session_db_data_field;
			} else {
				// Create a new row to be saved in the session table
				$db_row_class = self::$db_row_class;
				self::$session_db_row = new $db_row_class;
				// Make sure it has a primary key!
				if (count(self::$session_db_row->getPrimaryKey) != 1) {
					trigger_error(
						"The primary key of " . self::$session_db_row_class 
						. " has to consist of exactly 1 field!", 
						E_WARNING
					);
				}
				
				self::$session_db_row->$session_db_id_field = $id;
				return ''; // empty session. We return empty string instead of NULL.
			}
		} else {
			$sess_file = self::$session_save_path . "/sess_$id";
			if (!file_exists($sess_file)) {
				return null;
			}
			return (string) file_get_contents($sess_file);
		}
	}

	static function write ($id, $sess_data)
	{
		if (empty(self::$session_save_path)) {
			self::$session_save_path = self::savePath();
		}
		if (! empty(self::$session_db_connection)) {
			$data_field = self::$session_db_data_field;
			$updated_field = self::$session_db_updated_field;
			self::$session_db_row->$updated_field = date('Y-m-d H:i:s');
			self::$session_db_row->$data_field = $sess_data;
			self::$session_db_row->save();
		} else {
			$sess_file = self::$session_save_path . "/sess_$id";
			$fp = fopen($sess_file, "w");
			if (!$fp)
				return false;
			$return = fwrite($fp, $sess_data);
			fclose($fp);
			return $return;
		}
		return true;
	}

	static function destroy ($id)
	{
		if (! empty(self::$session_db_connection)) {
			self::$session_db
				->delete(self::$session_db_table)
				->where(array(self::$session_db_id_field => $id))
				->execute();
			return true;
		} else {
			$sess_file = self::$session_save_path . "/sess_$id";
			return (unlink($sess_file));
		}
	}

	static function gc ($max_lifetime)
	{
		if ($max_lifetime == 0)
			return false;
		if (! empty(self::$session_db_connection)) {
			//$real_now = date('Y-m-d H:i:s');
			//$timestamp = strtotime("$real_now -$max_lifetime seconds");
			$timestamp = time() - $max_lifetime;
			$datetime = date('Y-m-d H:i:s', $timestamp);
			self::$session_db
				->delete(self::$session_db_table)
				->where(array(self::$session_db_updated_field . '<' => $datetime))
				->execute();
		} else {
			foreach (glob(self::$session_save_path . "/sess_*") as $filename)
				if (filemtime($filename) + $max_lifetime < time())
					unlink($filename);
		}
		return true;
	}
	
	static function setNonce()
	{
		$snf = Pie_Config::get('pie', 'session', 'nonceField', 'nonce');
		self::start();
		if (!isset($_SESSION[$snf])) {
			$_SESSION[$snf] = md5(mt_rand().microtime());
		}
	}
	
	protected static $inited = false;
}
