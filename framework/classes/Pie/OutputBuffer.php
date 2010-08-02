<?php

/**
 * This class is meant to be used for output buffering
 */

/**
 * Allows use of output buffers that deal intelligently with exceptions.
 * Constructor implicitly calls ob_start().
 * The getClean() method calls ob_end_flush() repeatedly to flush buffers
 * which have been started but not flushed yet, after this one.
 * @package Pie
 */
class Pie_OutputBuffer
{
	public $level;
	
	/**
	 * Implicitly calls ob_start()
	 * @param string $handler
	 *  The output handler, such as 'gzip_handler'.
	 * @param boolean $throw_on_failure
	 *  Optional. If true, and throws an exception if failed
	 *  to create output buffer with this handler.
	 *  Otherwise, silently creates a "normal" output buffer.
	 */
	function __construct(
	 $handler = null, 
	 $throw_on_failure = false)
	{
		if (empty($handler) or !is_string($handler)) {
			ob_start();
		} else {
			$started = ob_start($handler);
			if (!$started) {
				if (!$throw_on_failure) {
					throw new Exception(
						"Pie_OutputBuffer with handler $handler could not be created"
					);
				}
				ob_start();
			}
		}
		$status = ob_get_status(false);
		$this->level = $status['level']; // nesting level of current buffer
	}
	
	/**
	 * The getClean() method calls ob_end_flush() repeatedly to flush buffers
	 * which have been started but not flushed yet, after this one.
	 */
	function getClean()
	{
		$this->flushHigherBuffers();
		return ob_get_clean();
	}
	
	/**
	 * The getClean() method calls ob_end_flush() repeatedly to flush buffers
	 * which have been started but not flushed yet, after this one.
	 */
	function endFlush()
	{
		@ob_end_flush();
	}
	
	function flushHigherBuffers()
	{
		$status = ob_get_status(false);
		$level = $status['level']; // nesting level of current buffer
		for ($i = $level; $i > $this->level; --$i) {
			@ob_end_flush();
		}
	}
}
