<?php

/**
 * This class deals with returning a response
 * @package Pie
 */
class Pie_Response
{
	/**
	 * Sets the content of a slot
	 * @param string $slot_name
	 *  The name of the slot.
	 * @param string $content
	 *  The content to set
	 */
	static function setSlot(
	 $slot_name,
	 $content)
	{
		self::$slots[$slot_name] = $content;
	}
	
	/**
	 * Gets the current content of a slot, if any.
	 * If slot content is null, then raises an event
	 * to try to fill the slot. If it is filled,
	 * returns the content. Otherwise, returns null.
	 * @param string|array $slot_name
	 *  The name of the slot.
	 * @param boolean $default_slot_name
	 *  Optional. If the slot named in $slot_name returns null,
	 *  the handler corresponding to the default slot will be called,
	 *  passing it the requested slot's name in the 'slot_name' parameter,
	 *  and its value will be returned instead.
	 *  Note: this does not fill the slot named $default_slot_name!
	 *  That is to say, the computed value is not saved, so that
	 *  the slot's handler is called again if it is ever consulted again.
	 * @param string $prefix
	 *  Optional. Sets a prefix for the HTML ids of all the elements in the slot.
	 * @return string|null
	 */
	static function fillSlot(
	 $slot_name,
	 $default_slot_name = null,
	 $prefix = null)
	{
		if (isset(self::$slots[$slot_name])) {
			return self::$slots[$slot_name];
		}
		$prev_slot_name = self::$slotName;
		self::$slotName = $slot_name;
		if (isset($prefix)) {
			Pie_Html::pushIdPrefix($prefix);
		}
		try {
			if (isset($default_slot_name)) {
				if (!Pie::canHandle("pie/response/$slot_name")) {
					$result = Pie::event(
						"pie/response/$default_slot_name",
						compact('slot_name')
					);
					if (isset(self::$slots[$slot_name])) {
						// The slot was already filled, while we were rendering it
						// so discard the $result and return the slot's contents
						return self::$slots[$slot_name];
					}
					return $result;
				}
			}
			$result = Pie::event("pie/response/$slot_name");
		} catch (Exception $e) {
			self::$slotName = $prev_slot_name;
			if (isset($prefix)) {
				Pie_Html::pieIdPrefix();
			}
			throw $e;
		}
		self::$slotName = $prev_slot_name;
		if (isset($prefix)) {
			Pie_Html::pieIdPrefix();
		}
		if (isset(self::$slots[$slot_name])) {
			// The slot was already filled, while we were rendering it
			// so discard the $result and return the slot's contents
			return self::$slots[$slot_name];
		}
		if (isset($result)) {
			self::setSlot($slot_name, $result);
			return $result;
		}
		
		// Otherwise, render default slot
		if (!isset($default_slot_name)) {
			return null;
		}
		return Pie::event(
			"pie/response/$default_slot_name",
			compact('slot_name')
		);
	}
	
	/**
	 * Gets all the slots that have been set
	 */
	static function getAllSlots()
	{
		return self::$slots;
	}
	
	/**
	 * Gets all the requested slots
	 * (uses Pie_Request::slots())
	 */
	static function getRequestedSlots()
	{
		$return = array();
		$slot_names = Pie_Request::slotNames();
		foreach ($slot_names as $sn) {
			$sn_parts = explode('.', $sn);
			$slot_name = end($sn_parts);
			$return[$slot_name] = self::fillSlot($slot_name);
		}
		return $return;
	}
	
	/**
	 * Adds an error
	 * @param Pie_Exception $exception
	 *  A PIE exception representing the error
	 */
	static function addError(
	 Exception $exception)
	{
		self::$errors[] = $exception;
	}
	
	/**
	 * Returns all the errors added so far to the response.
	 * @return array
	 */
	static function getErrors()
	{
		return self::$errors;
	}
	
	/**
	 * Adds a notice
	 * @param string $key
	 * @param string $notice
	 */
	static function addNotice($key, $notice)
	{
		self::$notices[$key] = $notice;
	}
	
	/**
	 * Returns all the notices added so far to the response.
	 * @return array
	 */
	static function getNotices()
	{
		return self::$notices;
	}
	
	/**
	 * Sets one of the attributes of a style to a value.
	 */
	static function setStyle($list_of_keys, $value = null)
	{
		$args = func_get_args();
		$p = new Pie_Parameters(self::$styles);
		call_user_func_array(array($p, 'set'), $args);
		
		// Now, for the slot
		if (isset(self::$slotName)) {
			if (!isset(self::$stylesForSlot[self::$slotName])) {
				self::$stylesForSlot[self::$slotName] = array();
			}
			$p = new Pie_Parameters(self::$stylesForSlot[self::$slotName]);
			call_user_func_array(array($p, 'set'), $args);
		}
	}
	
	/**
	 * Returns text describing all the styles inline which have been added with setStyle(),
	 * to be included between <style></style> tags.
	 *
	 * @param string $slot_name
	 *  Optional. If provided, returns only the stylesheets added while filling this slot.
	 * @return string 
	 */
	static function stylesInline($slot_name = null)
	{
		if (isset($slot_name)) {
			if (!isset(self::$stylesForSlot[$slot_name]))
				return null;
			$styles = self::$stylesForSlot[$slot_name];
		} else {
			$styles = self::$styles;
		}
		if (!is_array($styles))
			return '';
			
		$return = '';
		foreach ($styles as $selector => $style) {
			$return .= "\n\t$selector {\n\t\t";
			if (is_string($style)) {
				$return .= $style;
			} else {
				foreach ($style as $property => $value)
					$return .= "$property: $value;   ";
			}
			$return .= "\n\t}";
		}
		return $return;
	}
	
	/**
	 * Adds a line of script to be echoed in a layout
	 * @param string $line
	 *  The line of script
	 */
	static function addScriptLine($line)
	{
		// Now, for the slot
		if (isset(self::$slotName)) {
			if (!isset(self::$scriptLinesForSlot[self::$slotName])) {
				self::$scriptLinesForSlot[self::$slotName] = array();
			}
			self::$scriptLinesForSlot[self::$slotName][] = $line;
		}
		self::$scriptLines[] = $line;
	}
	
	/**
	 * Returns text describing all the scripts lines that have been added,
	 * to be included between <script></script> tags.
	 *
	 * @param string $slot_name
	 *  Optional. If provided, returns only the script lines added while filling this slot.
	 * @return string 
	 */
	static function scriptLines($slot_name = null)
	{
		if (isset($slot_name)) {
			if (!isset(self::$scriptLinesForSlot[$slot_name]))
				return null;
			$lines = self::$scriptLinesForSlot[$slot_name];
		} else {
			$lines = self::$scriptLines;
		}
		if (!is_array($lines))
			return '';
			
		$return = '';
		foreach ($lines as $line) {
			$return .= "\n$line\n";
		}
		return $return;
	}
	

	
	/**
	 * Adds a script reference to the response
	 *
	 * @param string $src
	 * @param string $type defaults to 'text/javascript'
	 * @return bool returns false if script was already added, else returns true
	 */
	static function addScript ($src, $type = 'text/javascript')
	{
		$modify = Pie::event('pie/response/addScript', compact('src', 'type'), 'before');
		if ($modify) {
			extract($modify);
		}

		foreach (self::$scripts as $script) {
			if ($script['src'] == $src && $script['type'] == $type)
				return false; // already added
		}
		self::$scripts[] = compact('src', 'type');
		
		// Now, for the slot
		if (isset(self::$slotName)) {
			if (!isset(self::$scriptsForSlot[self::$slotName])) {
				self::$scriptsForSlot[self::$slotName] = array();
			}
			foreach (self::$scriptsForSlot[self::$slotName] as $script) {
				if ($script['src'] == $src && $script['type'] == $type)
					return false; // already added
			}
			self::$scriptsForSlot[self::$slotName][] = compact('src', 'type');
		}
		
		return true;
	}

	/**
	 * Return the array of scripts added so far
	 *
	 * @param string $slot_name
	 *  Optional. If provided, returns only the stylesheets added while filling this slot.
	 * @param string $urls
	 *  Optional. If true, transforms all the 'src' values into URLs before returning.
	 * @return array the array of scripts added so far
	 */
	static function scriptsArray ($slot_name = null, $urls = true)
	{
		if (isset($slot_name)) {
			if (!isset(self::$scriptsForSlot[$slot_name]))
				return array();
			$scripts = self::$scriptsForSlot[$slot_name];
		} else {
			$scripts = self::$scripts;
		}
		if (!is_array($scripts))
			return array();
		if ($urls) {
			foreach ($scripts as $k => $r) {
				$scripts[$k]['src'] = Pie_Html::themedUrl($r['src']);
			}
		}
		return $scripts;
	}

	/**
	 * Returns markup for referencing all the scripts added so far
	 *
	 * @param string $slot_name
	 *  Optional. If provided, returns only the stylesheets added while filling this slot.
	 * @return string 
	 *  the script tags and their contents inline
	 */
	static function scriptsInline ($slot_name = null)
	{
		$scripts = self::scriptsArray($slot_name, false);
		if (empty($scripts))
			return '';
		
		$scripts_str = '';
		foreach ($scripts as $script) {
			$src = '';
			extract($script, EXTR_IF_EXISTS);

			$ob = new Pie_OutputBuffer();
			if (Pie_Valid::url($src)) {
				try {
					include($src);
				} catch (Exception $e) {}
			} else {
				list ($src, $filename) = Pie_Html::themedUrlAndFilename($src);
				try {
					Pie::includeFile($filename);
				} catch (Exception $e) {}
			}
			$scripts_str .= "\n/* Included inline from $src */\n"
			 . $ob->getClean();
		}
 		return Pie_Html::script($scripts_str);
	}

	/**
	 * Returns the HTML markup for referencing all the scripts added so far
	 * @param string $between
	 *  Optional text to insert between the <link> elements.
	 * @param string $slot_name
	 *  Optional. If provided, returns only the stylesheets added while filling this slot.
	 *
	 * @return string the HTML markup for referencing all the scripts added so far 
	 */
	static function scripts ($between = '', $slot_name = null)
	{
		$scripts = self::scriptsArray($slot_name);
		if (empty($scripts))
			return '';
			
		$tags = array();
		foreach ($scripts as $script) {
			$src = '';
			$media = 'screen, print';
			$type = 'text/css';
			extract($script, EXTR_IF_EXISTS);
			$tags[] = Pie_Html::tag(
				'script', 
				array('type' => $type, 'src' => $src)
			) . '</script>';
		}
		return implode($between, $tags);
	}

	/**
	 * Adds a stylesheet
	 *
	 * @param string $href
	 * @param string $media defaults to 'screen, print'
	 * @param string $type defaults to 'text/css'
	 * @return bool returns false if a stylesheet with exactly the same parameters has already been added, else true.
	 */
	static function addStylesheet ($href, $media = 'screen, print', $type = 'text/css')
	{
		$modify = Pie::event('pie/response/addStylesheet', compact('href', 'media', 'type'), 'before');
		if ($modify) {
			extract($modify);
		}

		foreach (self::$stylesheets as $stylesheet) {
			if ($stylesheet['href'] == $href && $stylesheet['media'] == $media &&
				 $stylesheet['type'] == $type) {
					// already added
					return false;
			}
		}
		self::$stylesheets[] = compact('href', 'media', 'type');
		
		// Now, for the slot
		if (isset(self::$slotName)) {
			if (!isset(self::$stylesheetsForSlot[self::$slotName])) {
				self::$stylesheetsForSlot[self::$slotName] = array();
			}
			foreach (self::$stylesheetsForSlot[self::$slotName] as $stylesheet) {
				if ($stylesheet['href'] == $href && $stylesheet['media'] == $media &&
					 $stylesheet['type'] == $type) {
						// already added
						return false;
				}
			}
			self::$stylesheetsForSlot[self::$slotName][] = compact('href', 'media', 'type');
		}
		
		return true;
	}

	/**
	 * Returns array of stylesheets that have been added so far
	 *
	 * @param string $slot_name
	 *  Optional. If provided, returns only the stylesheets added while filling this slot.
	 * @return array 
	 *  the array of stylesheets that have been added so far
	 * @param string $urls
	 *  Optional. If true, transforms all the 'src' values into URLs before returning.
	 */
	static function stylesheetsArray ($slot_name = null, $urls = true)
	{
		if (isset($slot_name)) {
			if (!isset(self::$stylesheetsForSlot[$slot_name])) {
				return array();
			} 
			$sheets = self::$stylesheetsForSlot[$slot_name];
		} else {
			$sheets = self::$stylesheets;
		}
		if (!is_array($sheets)) 
			return array();
		if ($urls) {
			foreach ($sheets as $k => $r) {
				$sheets[$k]['href'] = Pie_Html::themedUrl($r['href']);
			}
		}
		return $sheets;
	}

	/**
	 * Returns a <style> tag with the content of all the stylesheets included inline
	 * 
	 * @param $styles
	 * If not empty, this associative array contains styles which will be
	 * included at the end of the generated <style> tag.
	 * @param string $slot_name
	 *  Optional. If provided, returns only the stylesheets added while filling this slot.
	 *
	 * @return string 
	 *  the style tags and their contents inline
	 */
	static function stylesheetsInline ($styles = array(), $slot_name = null)
	{
		$styles = self::stylesInline($slot_name, false);
		if (empty($styles) and empty(self::$stylesheets))
			return '';
			
		$return = "<style type='text/css'>\n";
		if (!empty(self::$stylesheets)) {
			foreach (self::$stylesheets as $stylesheet) {
				$href = '';
				$media = 'screen, print';
				$type = 'text/css';
				extract($stylesheet, EXTR_IF_EXISTS);

				$ob = new Pie_OutputBuffer();
				if (Pie_Valid::url($href)) {
					try {
						include($href);
					} catch (Exception $e) {}
				} else {
					list ($href, $filename) = Pie_Html::themedUrlAndFilename($href);
					try {
						Pie::includeFile($filename);
					} catch (Exception $e) {}
				}
				$stylesheet = "\n/* Included inline from $href */\n"
				 . $ob->getClean();
				$return .= "$stylesheet\n";
			}
		}
		$return .= "/* Included inline from Pie_Response::stylesInline() */\n";
		$return .= $styles;
		$return .= "\n</style>";
		return $return;
	}

	/**
	 * Returns the HTML markup for referencing all the stylesheets added so far
	 * @param string $between
	 *  Optional text to insert between the <link> elements.
	 * @param string $slot_name
	 *  Optional. If provided, returns only the stylesheets added while filling this slot.
	 * 
	 * @return string the HTML markup for referencing all the stylesheets added so far 
	 */
	static function stylesheets ($between = '', $slot_name = null)
	{
		$stylesheets = self::stylesheetsArray($slot_name);
		if (empty($stylesheets))
			return '';
		$tags = array();
		foreach (self::$stylesheets as $stylesheet) {
			$href = '';
			$media = 'screen, print';
			$type = 'text/css';
			extract($stylesheet, EXTR_IF_EXISTS);
			//$return .= "<style type='$type' media='$media'>@import '$href';</style>\n";
			$tags[] = Pie_Html::tag('link', 
				array(
					'rel' => 'stylesheet', 
					'type' => $type, 
					'href' => $href, 
					'media' => $media
				));
		}
		return implode($between, $tags);
	}
	
	/**
	 * Gets or sets whether the response is buffered.
	 * @param boolean $new_value
	 *  Optional. If not present, just returns the current value of this setting.
	 *  If true or false, sets the setting to this value, and returns the setting's old value.
	 * @return boolean
	 */
	static function isBuffered($new_value = null)
	{
		$old_value = self::$isBuffered;
		if (isset($new_value)) {
			self::$isBuffered = $new_value;
		}
		return $old_value;
	}
	
	static function redirect($uri)
	{
		$url = Pie_Uri::url($uri);
		$level = ob_get_level();
		for ($i=0; $i<$level; ++$i) {
			ob_clean();
		}
		$result = Pie::event('pie/redirect', compact('uri', 'url'), 'before');
		if (isset($result)) {
			return $result;
		}
		header("Location: $url");
		return true;
	}

	/**
	 * Used to get/set output that the pop/response handler should consult.
         * @param string $new_output
         *  Optional. Pass a string here to return as output, instead of the usual layout.
	 *  Or, pass true here to indicate that we have already returned the output,
	 *  and to skip rendering a layout.
         * @param bool $overwrite
         *  Defaults to false. If an output string is already set, doesn't override it
         *  unless you pass true here.
         */
	static function output($new_output = null, $override = false)
	{
		static $output = null;
		if (isset($new_output)) {
			if (!isset($output) or $override === true) {
				$output = $new_output;
			}
		}
		return $output;
	}
	
	protected static $slots = array();
	
	protected static $scripts = array();
	protected static $stylesheets = array();
	protected static $styles = array();
	protected static $scriptLines = array();
	protected static $errors = array();
	protected static $notices = array();

	protected static $slotName = null;
	protected static $scriptsForSlot = array();
	protected static $stylesheetsForSlot = array();
	protected static $stylesForSlot = array();
	protected static $scriptLinesForSlot = array();
	
	protected static $isBuffered = true; // buffer responses by default
}
