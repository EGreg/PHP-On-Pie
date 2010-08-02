<?php

/**
 * This class lets you run test cases
 * @package Pie
 */

class Pie_TestCase
{
	/**
	 * Runs the tests
	 *
	 * @return array $results
	 *  Returns array of results. A result consists of 
	 */
	function run()
	{
		$results = array();
		
		$this->saveState();
		
		$class = get_class($this);
		$class_methods = get_class_methods($class);
		shuffle($class_methods); // execute tests in random order!
		foreach ($class_methods as $cm) {
			if (substr($cm, -4) == 'Test') {
				// Run the test
				if (is_callable(array($this, 'setUp'))) {
					$this->setUp();
				}
					//echo "running $class::$cm...\n";
				try {
					$ret = call_user_func(array($this, $cm));
					// $ret is not used for now.
					$results[$class][$cm] = array(self::TEST_SUCCEEDED, 'succeeded');
				} catch (Pie_Exception_TestCase $e) {
					// one of the predefined testcase outcomes occurred
					$results[$class][$cm] = array($e->getCode(), $e->getMessage());
				} catch (Exception $e) {
					$results[$class][$cm] = array(self::TEST_EXCEPTION, 'exception', $e);
				}
				if (is_callable(array($this, 'tearDown'))) {
					$this->tearDown();
				}
				$this->restoreState();
			}
		}
		$this->hasToRun = false;
		
		return $results;
	}
	
	/**
	 * get a fixture variable
	 */
	function get ($name, $default = null)
	{
		if (! isset($this->p))
			$this->p = new PieParameters();
		return $this->p->get($name, $default);
	}

	/**
	 * set up a fixture variable
	 */
	function set ($name, $value = null)
	{
		if (! isset($this->p))
			$this->p = new PieParameters();
		return $this->p->set($name, $value);
	}

	/**
	 * Clears a fixture variable
	 * @param string $name
	 *  The name of the variable
	 */
	function clear ($name)
	{
		if (! isset($this->p))
			$this->p = new PieParameters();
		return $this->p->clear($name);
	}

	/**
	 * Gets all the fixture variables.
	 * Typically, you would do extract($this->getAll())
	 * at the beginning of a test.
	 */
	function getAll ()
	{
		if (! isset($this->p))
			$this->p = new PieParameters();
		return $this->p->getAll();
	}
	
	/**
	 * Call to indicate the test failed
	 * @param string $message
	 *  Optional custom message.
	 */
	function testFailed($message = "failed")
	{
		$result = self::TEST_FAILED;
		throw new Pie_Exception_TestCaseFailed(compact('message', 'result'));
	}
	
	/**
	 * Call to indicate the test was skipped
	 * @param string $message
	 *  Optional custom message.
	 */
	function testSkipped($message = "skipped")
	{
		$result = self::TEST_SKIPPED;
		throw new Pie_Exception_TestCaseSkipped(compact('message', 'result'));
	}
	
	/**
	 * Call to indicate the test is not yet completely written.
	 * @param string $message
	 *  Optional custom message.
	 */
	function testIncomplete($message = "incomplete")
	{
		$result = self::TEST_INCOMPLETE;
		throw new Pie_Exception_TestCaseIncomplete(compact('message', 'result'));
	}
	
	/**
	 * Saves the initial state of the State
	 */
	private function saveState()
	{
		if (!$this->saved_super_globals) {
			$this->saved_super_globals = array(
				'GLOBALS' => $GLOBALS, 
				'_ENV' => $_ENV, 
				'_POST' => $_POST, 
				'_GET' => $_GET, 
				'_COOKIE' => $_COOKIE, 
				'_SERVER' => $_SERVER, 
				'_FILES' => $_FILES, 
				'_REQUEST' => $_REQUEST
			);
		}
		
		if (!$this->saved_p) {
			$this->saved_p = $this->p;
		}
	}
	
	/**
	 * Restores the initial state of the State
	 */
	private function restoreState()
	{
		$this->p = $this->saved_p;
		
		if (!empty($this->saved_super_globals)) {
			$GLOBALS = $this->saved_super_globals['GLOBALS'];
			$_ENV = $this->saved_super_globals['_ENV'];
			$_POST = $this->saved_super_globals['_POST'];
			$_GET = $this->saved_super_globals['_GET'];
			$_COOKIE = $this->saved_super_globals['_COOKIE'];
			$_SERVER = $this->saved_super_globals['_SERVER'];
			$_FILES = $this->saved_super_globals['_FILES'];
			$_REQUEST = $this->saved_super_globals['_REQUEST'];
		}

		return true;
	}
	
	public $p = null;
	public $saved_p = null;
	
	private $saved_super_globals = false;

	/**#@+
	 * Constants for PieTestCase results
	 */
	// green:
	const TEST_SUCCEEDED = 1;
	// yellow:
	const TEST_INCOMPLETE = 12;
	const TEST_SKIPPED = 13;
	// red:
	const TEST_FAILED = 20;
	const TEST_EXCEPTION = 21;
/**#@-*/
}

/// { aggregate classes for production
/// Exception/TestCaseFailed.php
/// Exception/TestCaseIncomplete.php
/// Exception/TestCaseSkipped.php
/// }
