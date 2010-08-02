<?php

class Pie_Exception_PhpError extends Pie_Exception
{
	/**
	 * Constructor.
	 * @param array $params
	 *  The following values are expected:
	 *  "errstr" => the error message to display
	 *  "errfile" =>
	 *  "errline" =>
	 *  "fixTrace" => fixes the trace array to
	 * @param array $input_fields
	 *  Same as in Pie_Exception.
	 */
	function __construct($params, $input_fields)
	{
		parent::__construct($params, $input_fields);
		if (!empty($params['fixTrace'])) {
			$this->fixTrace = true;
			if (isset($params['errfile']) && isset($params['errline'])) {
				$this->file = $params['errfile'];
				$this->line = $params['errline'];
			}
		}
	}

	function getTraceEx()
	{
		$trace = parent::getTrace();
		return array_slice($trace, 3);
	}

	function getTraceAsStringEx()
	{
		$str = parent::getTraceAsString();
		return implode("\n", array_slice(explode("\n", $str), 4));
	}

	protected $fixTrace = false;
};

Pie_Exception::add('Pie_Exception_PhpError', '(PHP error) $errstr');
