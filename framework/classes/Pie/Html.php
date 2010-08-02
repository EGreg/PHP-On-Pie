<?php

/**
 * This class lets you output various HTML markup tags
 * @package Pie
 */

class Pie_Html
{
	/**
	 * Generates a querystring based on the current $_GET
	 * @param array $fields
	 *  Associative array. The keys are the fields to take 
	 *  from $_GET, the values are the defaults.
	 * @param array $more_fields
	 *  The array to merge over the query array before
	 *  building the querystring.
	 * @return string
	 *  The resulting querystring.
	 */
	static function query(
	 $fields,
	 $more_fields = array())
	{
		$query_array = array();
		foreach ($fields as $field => $default) {
			$query_array[$field] = array_key_exists($field, $_GET)
			 ? $_GET[$field]
			 : $default;
		}
		$query_array = array_merge($query_array, $more_fields);
		$query = http_build_query($query_array);
	}
	
	/**
	 * Generates markup for a link element
	 * @param string|Pie_Uri $href
	 *  Could be a URL string, a Pie_Uri object, or a string
	 *  representing a Pie_Uri object.
	 * @param array $attributes
	 *  An associative array of additional attributes.
	 * @param string $contents 
	 *  If null, only the opening tag is generated. 
	 *  If a string, also inserts the contents and generates a closing tag.
	 *  If you want to do escaping on the contents, you must do it yourself.
	 *  If true, auto-closes the tag.
	 * @return string
	 *  The generated markup
	 */
	static function a(
	 $href, 
	 $attributes = array(),
	 $contents = null)
	{
		if (!is_array($attributes))
			$attributes = array();
		$tag_params = array_merge(
			compact('href'),
			$attributes
		);
		return self::tag('a', $tag_params, $contents);
	}
	
	/**
	 * Renders a form
	 *
	 * @param string|Pie_Uri $action
	 *  Could be a URL string, a Pie_Uri object, or a string
	 *  representing a Pie_Uri object.
	 * @param string $method
	 *  Defaults to 'post'. The method for the form submission.
	 * @param array $attributes 
	 *  An associative array of additional attributes.
	 *  (say that 4 times fast)
	 * @param string $contents 
	 *  If null, only the opening tag is generated. 
	 *  If a string, also inserts the contents and generates a closing tag.
	 *  If you want to do escaping on the contents, you must do it yourself.
	 * @return string
	 *  The generated markup
	 */
	static function form(
	 $action = '',
	 $method = 'post',
	 $attributes = array(),
	 $contents = null)
	{
		if (!is_string($method)) {
			throw new Exception("form method is not a string");
		}
		if (!is_array($attributes)) {
			$attributes = array();
		}
		$tag_params = array_merge(
			compact('action', 'method'),
			$attributes
		);
		return self::tag('form', $tag_params, $contents);
	}
	
	/**
	 * Renders pie-specific information for a form
	 * @param string $on_success
	 *  The URI or URL to redirect to in case of success
	 *  If you put "true" here, it uses $_REQUEST['_pie']['onSuccess'],
	 *  or if it's not there, then Pie_Dispatcher::uri()
	 * @param string $on_errors
	 *  Optional. The URI or URL to redirect to in case of errors
	 *  If you put "true" here, it uses $_REQUEST['_pie']['onSuccess'],
	 *  or if it's not there, then Pie_Dispatcher::uri()
	 * @param string $session_nonce_field
	 *  Optional. The name of the nonce field to use in the session.
	 *  If the config parameter "pie"/"session"/"nonceField" is set, uses that.
	 * @return string
	 *  The generated markup
	 */
	static function formInfo(
	 $on_success,
	 $on_errors = null,
	 $session_nonce_field = null)
	{
		$uri = Pie_Dispatcher::uri();
		if ($on_success === true) {
			$on_success = Pie::ifset($_REQUEST['_pie']['onSuccess'], $uri);
		}
		if ($on_errors === true) {
			$on_errors = Pie::ifset($_REQUEST['_pie']['onSuccess'], $uri);
		}
		$hidden_fields = array();
		if (isset($on_success)) {
			$hidden_fields['_pie[onSuccess]'] = Pie_Uri::url($on_success);
		}
		if (isset($on_errors)) {
			$hidden_fields['_pie[onErrors]'] = Pie_Uri::url($on_errors);
		}
		if (!isset($session_nonce_field)) {
			$session_nonce_field = Pie_Config::get('pie', 'session', 'nonceField', 'nonce');
		}
		if (isset($session_nonce_field)) {
			if (!isset($_SESSION['pie'][$session_nonce_field])) {
				$_SESSION['pie'][$session_nonce_field] = uniqid();
			}
			$hidden_fields['_pie[nonce]'] = $_SESSION['pie'][$session_nonce_field];
		}
		return self::hidden($hidden_fields);
	}
	
	/**
	 * Renders a bunch of hidden inputs
	 * 
	 * @param array $list
	 *  An associative array of fieldname => fieldvalue pairs.
	 * @param array|string $keys
	 *  An array of keys to precede the keys in the associative array.
	 *  If a string is passed, it becomes the name of the field (array).
	 *  Defaults to empty. Used mainly during recursion.
	 * @param boolean class_attributes
	 *  Defaults to true. If true, generates the class attribute name
	 *  from the name of each field.
	 */
	static function hidden (
	 array $list = array(), 
	 $keys = null,
	 $class_attributes = true)
	{
		if (!isset($keys)) {
			$keys = array();
		} else if (is_string($keys)) {
			$keys = array($keys);
		}
	
		$hidden_fields = array();

		$name = '';
		foreach ($keys as $key) {
			if (!$name) {
				$name = $key;
			} else {
				$name .= "[$key]";
			}
		}

		foreach ($list as $key => $value) {
			$name2 = $name ? $name.'['.$key.']' : $key;
			$class = ($class_attributes === true)
				? preg_replace('/^[A-Za-z0-9]/', '_', $name2)
				: null;
			if (!is_array($value)) {
				$hidden_fields[] = self::tag('input', array(
					'type' => 'hidden', 
					'name' => $name2, 
					'value' => $value,
					'class' => $class
				));
			}
		}
		$html = implode('', $hidden_fields);
		foreach ($list as $key => $value) {
			if (is_array($value)) {
				$keys2 = $keys;
				$keys2[] = $key;
				$html .= self::hidden($value, $keys2);
			}
		}
		return $html;
	}
	
	/**
	 * Renders a form input
	 *
	 * @param string $name 
	 *  The name of the input. Will be sanitized.
	 * @param string $value 
	 *  The value of the input. Will be sanitized.
	 * @param array $attributes 
	 *  An array of additional attributes to render. 
	 *  Consists of name => value pairs.
	 * @param string $contents 
	 *  If null, only the opening tag is generated. 
	 *  If a string, also inserts the contents and generates a closing tag.
	 *  If you want to do escaping on the contents, you must do it yourself.
	 * @return string 
	 * 	The generated markup
	 */
	static function input (
		$name, 
		$value, 
		$attributes = array(), 
		$contents = null)
	{
		if (!isset($attributes))
			$attributes = array();
		$tag_params = array_merge(compact('name', 'value'), $attributes);
		return self::tag('input', $tag_params, $contents);
	}
	
	/**
	 * Renders a textarea in a form
	 *
	 * @param string $name 
	 *  The name of the input. Will be sanitized.
	 * @param string $rows 
	 *  The number of rows in the textarea.
	 * @param string $cols 
	 *  The number of columns in the textarea.
	 * @param array $attributes 
	 *  An array of additional attributes to render. 
	 *  Consists of name => value pairs.
	 * @param string $contents 
	 *  If null, only the opening tag is generated. 
	 *  If a string, also inserts the contents and generates a closing tag.
	 *  If you want to do escaping on the contents, you must do it yourself.
	 * @return string 
	 * 	The generated markup
	 */
	static function textarea (
		$name, 
		$rows, 
		$cols,
		$attributes = array(), 
		$contents = null)
	{
		if (!isset($attributes))
			$attributes = array();
		$tag_params = array_merge(compact('name', 'rows', 'cols'), $attributes);
		return self::tag('input', $tag_params, $contents);
	}
	
	/**
	 * Renders a select tag
	 *
	 * @param string $name 
	 *  The name of the input. Will be sanitized..
	 * @param array $attributes 
	 *  An array of additional attributes to render. 
	 *  Consists of name => value pairs.
	 * @return string 
	 * 	The generated markup
	 */
	static function select (
		$name,  
		$attributes = array())
	{
		if (!isset($attributes))
			$attributes = array();
		$tag_params = array_merge(compact('name'), $attributes);
		return self::tag('select', $tag_params);
	}
	
	/**
	 * Renders a series of options for a select tag from an associative array we already have
	 *
	 * @param array $list 
	 *  Associative array of value => caption.
	 * @param array $ids
	 *  Either an associative array of key => id (in the HTML element sense) pairs, or
	 * @param string|int $selectedKey 
	 *  Basically the value of the selected option
	 * @param string $includeBlank 
	 *  If null, don't include a blank option. 
	 *  Otherwise, make a blank item (with value="") and 
	 *  caption=$includeBlank if it's a string, or "" otherwise.
	 * @param string $between 
	 *  The text to insert in the markup between the generated elements.
	 *  Since this text won't be shown in the browser, it's just for source formatting purposes.
	 * @param array $attributes 
	 *  An array of additional attributes to render. Consists of name => value pairs.
	 * @return string 
	 *  The generated markup
	 */
	static function options (
		$list, 
		$ids = '',
		$selectedKey = null, 
		$includeBlank = false, 
		$between = '',
		$attributes = array())
	{	
		if (! is_array($list))
			return '</select><div class="pie_error">The list for options must be an array.</div>';
		if (!isset($attributes))
			$attributes = array();	
		if (empty($ids))
			$ids = 'options'.mt_rand(100, 1000000);

		$i = 0;
		$html_parts = array();
		foreach ($list as $key => $value) {
			if (is_string($ids)) {
				$id = $ids . '_' . $i;
			} else if (is_array($ids)) {
				$id = isset($ids[$key]) ? $ids[$key] : reset($ids) . '_' . $i;
			}
			$attributes2 = self::copyAttributes($attributes, $key);
			$attributes2['value'] = $key;
			$attributes2['id'] = $id;
			if ("$key" === "$selectedKey") {
				$attributes2['selected'] = 'selected';
			}
			$html_parts[] = self::tag('option', $attributes2, $value);
			++ $i;
		}
		
		$blank_option_html = '';
		if (isset($includeBlank) && $includeBlank !== false) {
			$blankCaption = is_string($includeBlank) ? $includeBlank : '';
			if (! isset($selectedKey) or $selectedKey === '') {
				$blank_option_html = '<option value="" selected="selected">' 
				 . self::text($blankCaption) .
				 '</option>';
			} else {
				$blank_option_html = '<option value="">' . $blankCaption . '</option>';
			}
		}
		
		return $blank_option_html . implode($between, $html_parts);
	}

	/**
	 * Renders a series of checkboxes from an associative array we already have
	 *
	 * @param string $name 
	 *  The name of the input
	 * @param array $list 
	 *  Associative array of value => caption.
	 * @param array $ids
	 *  Either an associative array of key => id (in the HTML element sense) pairs, or
	 *  a string. If a string, then a counter (1, 2, 3) is appended to each subsequent id.
	 * @param array $checked 
	 *  Associative array indicating which checkboxes are checked. If a key from $list 
	 *  is present as a key in this array, that checkbox is checked.
	 * @param string $between 
	 *  The text to insert in the markup between the generated elements
	 * @param array $attributes 
	 *  An array of additional attributes to render. 
	 *  Consists of name => value pairs.
	 * @return string 
	 *  The generated markup
	 */
	static function checkboxes (
		$name, 
		$list, 
		$ids = '',
		$checked = array(), 
		$between = '', 
		$attributes = array())
	{
		if (! is_array($list))
			return '<div class="pie_error">The list for checkboxes must be an array.</div>';
		if (!isset($checked))
			$checked = array();
		if (!isset($attributes))
			$attributes = array();	
		if (empty($ids))
			$ids = 'checkboxes'.mt_rand(100, 1000000);

		$i = 0;
		$html_parts = array();
		foreach ($list as $key => $value) {
			if (is_string($ids)) {
				$id = $ids . '_' . $i;
			} else if (is_array($ids)) {
				$id = isset($ids[$key]) ? $ids[$key] : reset($ids) . '_' . $i;
			}
			$attributes2 = self::copyAttributes($attributes, $key);
			$attributes2['type'] = 'checkbox';
			$attributes2['name'] = $name;
			$attributes2['value'] = $key;
			$attributes2['id'] = $id;
			if (array_key_exists($key, $checked)) {
				$attributes2['checked'] = 'checked';
			}
			$html_parts[] = self::tag('input', $attributes2, true)
				. self::tag('label', array('for' => $id), self::text($value));
			++ $i;
		}
		return implode($between, $html_parts);
	}

	/**
	 * Renders a series of radio buttons from an associative array we already have
	 *
	 * @param string $name The name of the input
	 * @param array $list 
	 *  Associative array of value => caption.
	 * @param array $ids
	 *  Either an associative array of key => id (in the HTML element sense) pairs, or
	 * @param string $selectedKey 
	 *  Basically the value of the selected radiobutton
	 * @param string $between 
	 *  The text to insert in the markup between the generated elements
	 * @param array $attributes 
	 *  An array of additional attributes to render.
	 *  Consists of name => value pairs.
	 * @return string 
	 *  The generated markup
	 */
	static function radios (
		$name, 
		$list, 
		$ids = '', 
		$selectedKey = null, 
		$between = '', 
		$attributes = array())
	{
		if (! is_array($list))
			return '<div class="pie_error">The list for radios must be an array.</div>';
		if (!isset($attributes))
			$attributes = array();
		if (empty($ids))
			$ids = 'radios'.mt_rand(100, 1000000);

		$i = 0;
		$html_parts = array();
		foreach ($list as $key => $value) {
			if (is_string($ids)) {
				$id = $ids . '_' . $i;
			} else if (is_array($ids)) {
				$id = isset($ids[$key]) ? $ids[$key] : reset($ids) . '_' . $i;
			}
			$attributes2 = $attributes;
			$attributes2['type'] = 'radio';
			$attributes2['name'] = $name;
			$attributes2['value'] = $key;
			$attributes2['id'] = $id;
			if ($key == $selectedKey) {
				$attributes2['checked'] = 'checked';
			}
			$html_parts[] = self::tag('input', $attributes2, true)
				. self::tag('label', array('for' => $id), self::text($value));
			++ $i;
		}
		return implode($between, $html_parts);
	}
	
	/**
	 * Renders a series of buttons from an associative array we already have
	 *
	 * @param string $name
	 *  The name of the input
	 * @param array $list 
	 *  Associative array of value => caption.
	 * @param array $ids
	 *  Either an associative array of key => id (in the HTML element sense) pairs, or
	 * @param string $between 
	 *  The text to insert in the markup between the generated elements
	 * @param array $attributes 
	 *  An array of additional attributes to render.
	 *  Consists of name => value pairs.
	 * @return string 
	 *  The generated markup
	 */
	static function buttons (
		$name, 
		$list, 
		$ids = '', 
		$between = '', 
		$attributes = array())
	{
		if (! is_array($list))
			return '<div class="pie_error">The list for buttons must be an array.</div>';
		if (!isset($attributes))
			$attributes = array();
		if (empty($ids))
			$ids = 'buttons'.mt_rand(100, 1000000);

		$i = 0;
		$html_parts = array();
		foreach ($list as $key => $value) {
			if (is_string($ids)) {
				$id = $ids . '_' . $i;
			} else if (is_array($ids)) {
				$id = isset($ids[$key]) ? $ids[$key] : reset($ids) . '_' . $i;
			}
			$attributes2 = self::copyAttributes($attributes, $key);
			$attributes2 = array_merge(
				array(
					'type' => 'button',
					'name' => $name,
					'value' => $key,
					'id' => $id
				), 
				$attributes2
			);
			$html_parts[] = self::tag('button', $attributes2, $value);
			++ $i;
		}
		return implode($between, $html_parts);
	}
	
	/**
	 * Renders an img tag
	 *
	 * @param string $src 
	 *  The source of the image. Will be subjected to theming before being rendered.
	 * @param string $alt
	 *  The alternative text to display in place of the image.
	 * @param array $attributes
	 *  An array of additional attributes to render. 
	 *  Consists of name => value pairs.
	 * @return string 
	 *  The generated markup
	 */
	static function img (
		$src, 
		$alt = 'image',
		$attributes = array())
	{
		if (!is_array($attributes))
			$attributes = array();
		if (!is_string($alt))
			$alt = 'not a string';
		$tag_params = array_merge(compact('src', 'alt'), $attributes);
		return self::tag('img', $tag_params);
	}
	
	/**
	 * Renders a div with some id and classes
	 *
	 * @param string $id 
	 *  The id of the div. It will be prefixed with the current id prefix.
	 * @param string $class 
	 *  The classes of the div. Could be a string or an array of strings.
	 * @param array $attributes
	 *  An array of additional attributes to render. 
	 *  Consists of name => value pairs.
	 * @param $content
	 *  The content of the label
	 * @return string 
	 *  The generated markup
	 */
	static function div (
		$id, 
		$class = '',
		$attributes = array(), 
		$contents = null)
	{
		if (!is_array($attributes))
			$attributes = array();
		$tag_params = array_merge(compact('id', 'class'), $attributes);
		return self::tag('div', $tag_params, $contents);
	}
	
	/**
	 * Renders a label for some element
	 *
	 * @param string $for 
	 *  The id of the element the label is tied to. 
	 *  It will be prefixed with the current id prefix
	 * @param array $attributes
	 *  An array of additional attributes to render. 
	 *  Consists of name => value pairs.
	 * @param $contents
	 *  The contents of the label
	 * @return string 
	 *  The generated markup
	 */
	static function label (
		$for, 
		$attributes = array(),
		$contents = null)
	{
		if (!is_array($attributes))
			$attributes = array();
		$tag_params = array_merge(compact('for'), $attributes);
		return self::tag('label', $tag_params, $contents);
	}
	
	/**
	 * Renders a series of buttons from an associative array we already have
	 *
	 * @param string $name
	 *  The name of the input
	 * @param string $value 
	 *  You can put a date here, as a string
	 * @param array $options
	 *  Options include the following:
	 *   "year_from" => the first year in the selector
	 *   "year_to" => the last year in the selector
	 * @param array $attributes 
	 *  An array of additional attributes to render.
	 *  Consists of name => value pairs.
	 * @return string 
	 *  The generated markup
	 */
	static function date(
		$name,
		$value = null,
		$options = null,
		$attributes = array())
	{
		$id = isset($attributes['id']) ? $attributes['id'] : '';
		$year_from = isset($options['year_from'])
		 ? $options['year_from']
		 : 1900;
		$year_to = isset($options['year_to'])
		 ? $options['year_to']
		 : date('Y');
		$years = array(0 => 'year');
		for ($i=$year_to; $i>=$year_from; --$i) {
			$years[$i] = (string)$i;
		}
		$months = array(
			0 => 'month',
			1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
			5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
			9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
		);
		$days = array(0 => 'day');
		for ($i=1; $i<=31; ++$i) {
			$days[$i] = (string)$i;
		}
		$dp = getdate(strtotime($value));
		$year = (isset($dp['year'])) ? $dp['year'] : null;
		$month = (isset($dp['mon'])) ? $dp['mon'] : null;
		$day = (isset($dp['mday'])) ? $dp['mday'] : null;
		$attributes['name'] = $name . '_year';
		if ($id) $attributes['id'] = $id . '_year';
		$year_select = self::tag('select', $attributes) 
		 . self::options($years, $id, $year)
		 . "</select>";
		$attributes['name'] = $name . '_month';
		if ($id) $attributes['id'] = $id . '_month';
		$month_select = self::tag('select', $attributes) 
		 . self::options($months, $id,  $month)
		 . "</select>";
		$attributes['name'] = $name . '_day';
		if ($id) $attributes['id'] = $id . '_day';
		$day_select = self::tag('select', $attributes) 
		 . self::options($days, $id, $day)
		 . "</select>";
		// TODO: consult the locale
		return "$month_select$day_select$year_select";
	}

	/**
	 * Renders a different tag based on what you specified.
	 * @param string $type
	 *  The type of the tag. Could be one of
	 *  'static', 'boolean', 'text', 'submit', 'hidden',
	 *  'textarea', 'password', 'select', 
	 *  'radios', 'checkboxes', 'buttons',
	 *  'image', or the name of a tag.
	 * @param array $attributes
	 *  The attributes for the resulting element.
	 * @param array $value
	 *  The value to represent using the resulting element.
	 *  If many options present, tries to select this value.
	 * @param array $options
	 *  Associative array of options, used if the tag type is
	 *  'select', 'radios' or 'checkboxes'.
	 * @return string
	 *  The generated markup
	 */
	static function smartTag(
		$type, 
		$attributes = array(), 
		$value = null, 
		$options = null)
	{
		if (!is_array($attributes))
			$attributes = array();

		$id = isset($attributes['id']) ? $attributes['id'] : null;

		switch ($type) {
			case 'static':
				unset($attributes['name']);
				if (empty($options['date'])) {
					$display = isset($options[$value]) ? $options[$value] : $value;
				} else {
					$display = date($options['date'], strtotime($value));
				}
				return self::tag('span', $attributes, $display);
			
			case 'boolean':
				$attributes['type'] = 'checkbox';
				if (!empty($value))
					$attributes['checked'] = 'checked';
				return self::tag('input', $attributes);
				break;
				
			case 'text':
			case 'submit':
			case 'hidden':
				$attributes['type'] = $type;
				$attributes['value'] = $value;
				return self::tag('input', $attributes);
				break;
			
			case 'textarea':
				if (!isset($attributes['rows']))
					$attributes['rows'] = 5;
				if (!isset($attributes['cols']))
					$attributes['cols'] = 20;
				return self::tag('textarea', $attributes, self::text($value));
				break;
	
			case 'password':
				$attributes['type'] = 'password';
				$attributes['maxlength'] = 64;
				$attributes['value'] = ''; // passwords should be cleared
				return self::tag('input', $attributes);
				break;
	
			case 'select':
				return self::tag('select', $attributes)
				. self::options($options, $id, $value)
				. "</select>";
				break;
				
			case 'radios':
				unset($attributes['value']);
				return "<div>"
				. self::radios($attributes['name'], $options, $id, $value, "</div><div>", $attributes)
				. "</div>";
				break;

			case 'checkboxes':
				unset($attributes['value']);
				if (!isset($value) or !is_array($value))
					$value = array();
				return "<div>"
				. self::checkboxes($attributes['name'], $options, $id, $value, "</div><div>", $attributes)
				. "</div>";
				break;
				
			case 'buttons':
				unset($attributes['value']);
				return "<div>"
				. self::buttons($attributes['name'], $options, $id, '', $attributes)
				. "</div>";
				break;
				
			case 'image':
				$attributes['src'] = $value;
				$attributes['alt'] = $type;
				return self::tag('img', $attributes);
				break;
				
			case 'date':
				return self::date($attributes['name'], $value, $options, $attributes);
				
			default:
				return self::tag($type, $attributes, $value);
		}
	}
	
	static function render($object)
	{
		if (is_callable($object, "__toMarkup")) {
			return $object->__toMarkup();
		} else {
			return (string)$object;
		}
	}
	
	
	/**
	 * Renders an swf object using the standard <object> tag, which 
	 * hopefully all new modern browsers already support.
	 * 
	 * @param string $movie_url
	 *  The (relative or absolute) url of the movie
	 * @param array $flash_params 
	 *  An array of additional <param> elements to render within the <object> element.
	 *  Consists of name => value pairs. Note that the parameter with name="movie"
	 *  is always rendered.
	 * @param array $attributes 
	 *  An array of additional attributes to render. Consists of name => value pairs.
	 *  Don't forget to include "codebase", "width", "height" and "classid"
	 * @return string
	 *  The resulting markup
	 */
	static function swf (
		$movie_url,
		$flash_params = array(),
		$attributes = array())
	{		
		$contents = '';
		$flash_params['movie'] = self::text($movie_url);
		foreach ($flash_params as $name => $value) {
			$contents .= self::tag('param', compact('name', 'value'));
		}
		
		// Here, we'll only render the object tag
		// Most modern browsers should see it.
		if (!is_array($attributes))
			$attributes = array();
		$tag_params = array_merge(compact('data'), $attributes);
		return self::tag('object', $tag_params, $contents);
	}
	
	/**
	 * Renders a script (probably a javascript)
	 *
	 * @param string $script 
	 *  The actual script, as text
	 * @param bool $cdata 
	 *  Whether to enclose in <![CDATA[ tags.
	 *  You can also just pass an array of attributes here,
	 *  in which it will override subsequent parameters.
	 * @param bool $comment 
	 *  Whether to enclose in HTML comments
	 * @param string $type 
	 *  The type of the script
	 * @return string
	 *  The generated markup.
	 */
	static function script (
		$script, 
		$cdata = true, 
		$comment = false, 
		$type = 'text/javascript')
	{
		$options = compact('type');
		if (is_array($cdata)) {
			$options = $cdata;
			$cdata = $options['cdata'];
			unset($options['cdata']);
			$comment = $options['comment'];
			unset($options['comment']);
		}
		$return = "\n".self::tag('script', $options);
		if ($cdata) {
			$return .= "\n// <![CDATA[\n";
		} else if ($comment) {
			$return .= "<!-- \n"; 
		} else {
			$return .= "\n";
			$script = self::text($script);
		}
		$return .= $script;
		if ($cdata) {
			$return .= "\n// ]]> \n"; 
		} else if ($comment) {
			$return .= "\n//-->";
		} else {
			$return .= "\n";
		}
		$return .= "</script>\n";
		
		return $return;
	}
	
	/**
	 * Renders an arbitrary HTML tag
	 *
	 * @param string $tag
	 *  The tag name of the element
	 * @param array $attributes 
	 *  An array of additional attributes to render. Consists of name => value pairs.
	 * @param string $contents 
	 *  If null, only the opening tag is generated. 
	 *  If a string, also inserts the contents and generates a closing tag.
	 *  If you want to do escaping on the contents, you must do it yourself.
	 *  If true, auto-closes the tag.
	 * @return unknown
	 */
	static function tag (
		$tag, 
		$attributes = array(), 
		$contents = null)
	{
		if (!is_string($tag))
			throw new Exception('tag name is not a string');

		if (!is_array($attributes))
			$attributes = array();
			
		$attributes = self::attributes(
			$attributes, ' ', true, $tag
		);
		if (is_numeric($contents)) {
			$contents = (string)$contents;
		}
		if (is_string($contents)) {
			$return = "<$tag $attributes>$contents</$tag>";
		} else if ($contents === true) {
			$return = "<$tag $attributes />";
		} else {
			$return = "<$tag $attributes>";
		}
		return $return;
	}
	
	/**
	 * Escapes a string, converting all HTML entities
	 * into plain text equivalents.
	 * @return string $content
	 *  The string to escape
	 * @return string
	 */
	static function text(
	 $content)
	{
		return htmlentities($content, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Escapes a string, so it can be outputted within
	 * javascript. Note that this can be used within
	 * js files as well as inline scripts. However,
	 * inline scripts should be html-escaped or
	 * enclosed within <![CDATA[ ... ]]>
	 * So use Html::script().
	 *
	 * @return string $content
	 *  The string to escape
	 * @return string
	 */
	static function json(
	 $content)
	{
		self::text(json_encode($content));
	}	
	
	/**
	 * Returns an HTML element ID, constrained to alphanumeric
	 * characters with underscores, and possibly prefixed.
	 * @param string $id
	 *  Any string
	 * @return string
	 */
	static function id($id)
	{
		$id = preg_replace('/[^A-Za-z0-9]/', '_', $id);
		if (empty(self::$id_prefix)) {
			return $id;
		}
		return self::$id_prefix . $id;
	}
	
	/**
	 * Generates a string from an attribute array
	 *
	 * @param array $attributes
	 *  Associative array of name => value pairs.
	 * @param string $between
	 *  The text to insert between the attribute="value"
	 * @param string $escape
	 *  Whether to escape the attribute names and values.
	 * @return unknown
	 */
	protected static function attributes (
		array $attributes, 
		$between = ' ', 
		$escape = true, 
		$tag = null)
	{
		if (Pie_Config::get('pie', 'html', 'w3c', true)) {
			$defaults = array(
				'img' => array(
					'src' => '',
					'alt' => 'image'
				),
				'a' => array(
					'href' => '#missing_href'
				),
				'form' => array(
					'action' => ''
				),
				'textarea' => array(
					'rows' => 5,
					'cols' => 20
				),
				'meta' => array(
					'content' => ''
				),
				'applet' => array(
					'width' => '300px',
					'height' => '100px'
				),
				'optgroup' => array(
					'label' => 'group'
				),
				'map' => array(
					'name' => 'map'
				),
				'param' => array(
					'name' => '_pie_missing'
				),
				'basefont' => array(
					'size' => '100'
				),
				'bdo' => array(
					'dir' => '/'
				),
				'script' => array(
					'type' => 'text/javascript'
				),
				'style' => array(
					'type' => 'text/css'
				),
				'object' => array(
					'classid' => "_pie_missing", 
					'codebase' => "http://download.macromedia.com/pub/shockwave /cabs/flash/swflash.cab#version=9,0,115,0", 
					'width' => '550',
					'height' => '400'
				),
			);
			if (isset($defaults[$tag]) and is_array($defaults[$tag])) {
				$attributes = array_merge($defaults[$tag], $attributes);
			}
		}
		
		$result = '';
		$i = 0;
		foreach ($attributes as $name => $value) {
			if (!isset($value)) {
				continue; // skip null attributes
			}
			$name2 = $name;
			if (strpos(strtolower($tag), 'frame') !== false and strtolower($name) == 'src') {
				$name2 = 'href'; // treat the src as href
			}
			if (strtolower($tag) == 'link' and strtolower($name) == 'href') {
				$name2 = 'src'; // treat the href as src
			}

			switch (strtolower($name2)) {
				case 'href': // Automatic unrouting of this attribute
					$href = true;
				case 'action': // Automatic unrouting of this attribute
					$value = Pie_Uri::url($value);
					if ($value === false) {
						$value = '#_pie_bad_url';
					}
					break;
				case 'src': // Automatically prefixes theme url if any
					list ($value, $filename) = self::themedUrlAndFilename($value);
					break;
				case 'id': // Automatic prefixing of this attribute
				case 'for': // For labels, too
					if (! empty(self::$id_prefix)) {
						$value = self::$id_prefix . $value;
					}
					break;
			}
			if ($escape) {
				$name = self::text($name);
				$value = self::text($value);
			}
			$result .= ($i > 0 ? $between : '') . $name . '="' . $value . '"';
			++ $i;
		}
		return $result;
	}
	
	/**
	 * Copies attributes from a given array. Traverses the $attributes array.
	 * If the value is a string, copies it over. If it is an array, checks
	 * whether it contains $key and if it does, copies the value over.
	 * @param array $attributes
	 *  An associative array of attributes
	 * @param string $key
	 *  The key of the field being considered. 
	 * @return array
	 */
	protected static function copyAttributes($attributes, $key)
	{
		$result = array();
		foreach ($attributes as $k => $v) {
			if (is_array($v)) {
				if (array_key_exists($key, $v)) {
					$result[$k] = $v[$key];
				}
			} else {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/**
	 * Sets a new id prefix to prefix all ids rendered by Markup.
	 * It gets pushed on top of the stack and can be pieped later.
	 * @param string $id_prefix
	 *  The prefix to apply to all ids rendered by Markup after this
	 * @return string|null
	 *  The prefix previously on top of the stack, if any
	 */
	static function pushIdPrefix ($id_prefix)
	{
		$prev_prefix = self::$id_prefix;
		array_push(self::$id_prefixes, $id_prefix);
		self::$id_prefix = $id_prefix;
		return $prev_prefix;
	}

	/**
	 * Pies the last id prefix.
	 * Now all ids rendered by Pie_Html will be prefixed with the
	 * id previously on top of the stack, if any.
	 * @return string|null
	 *  The prefix that has been pieped, if any
	 */
	static function pieIdPrefix ()
	{
		if (count(self::$id_prefixes) <= 1)
			throw new Exception("Nothing to pie from prefix stack");
		$pieped_prefix = array_pop(self::$id_prefixes);
		self::$id_prefix = end(self::$id_prefixes);
		return $pieped_prefix;
	}

	/**
	 * The current prefix that will be applied to all ids
	 * rendered by Pie_Html.
	 * @return string|null
	 *  The prefix that is currently at the top of the prefix stack.
	 */
	static function getIdPrefix ()
	{
		return self::$id_prefix;
	}

	/**
	 * Pushes a new theme url  to the end of the cascade -- 
	 * if a theme file doesn't exist, we go backwards through the cascade
	 * and if we locate it under a previous theme url, we use that one.
	 * NOTE: If your webserver supports .htaccess files, you can implement
	 * cascading themes much more efficiently: simply push ONE theme url
	 * using tihs function, and implement the cascade using .htaccess files.
	 * @param string $theme_url
	 *  The url to be prepended to all non-absolute "src" attributes 
	 * (except for iframes) rendered by Pie_Html
	 * @return string|null
	 *  The theme url previously at the end of the cascade, if any
	 */
	static function pushThemeUrl ($theme_url)
	{
		$prev_theme_url = Pie_Config::get('pie', 'theme_url');
		Pie_Config::set('pie', 'theme_urls', null, $theme_url);
		Pie_Config::set('pie', 'theme_url', $theme_url);
		return $prev_theme_url;
	}

	/**
	 * The current theme url applied to all "src" attributes (except for iframes)
	 * rendered by Pie_Html.
	 * @return string|null
	 *  The theme url that is currently at the end of the cascade, i.e. was pushed last.
	 */
	static function themeUrl ()
	{
		return Pie_Config::get('pie', 'theme_url', Pie_Request::baseUrl());
	}
	
	/**
	 * Gets the url and filename of a themed file
	 * @param string $file_path
	 *  Basically the subpath of the file underneath the web or theme directory
	 */
	static function themedUrlAndFilename ($file_path)
	{
		$filename = false;
		$theme_url = Pie_Uri::url(self::themeUrl());
		$theme_urls = Pie_Config::get('pie', 'theme_urls', array(null));
		if (!Pie_Valid::url($file_path)) {
			$c = count($theme_urls);
			if ($c > 1) {
				// At least two theme URLs have been loaded
				// Do the cascade
				for ($i = $c - 1; $i >= 0; -- $i) {
					try {
						$filename = Pie_Uri::filenameFromUrl(
							$theme_urls[$i] . '/' . $file_path);
					} catch (Exception $e) {
						continue;
					}
					if (file_exists($filename)) {
						$theme_url = $theme_urls[$i];
						break;
					}
				}
			}
			$file_path = $theme_url . '/' . $file_path;
		}
		if (empty($filename)) {
			try {
				$filename = Pie_Uri::filenameFromUrl($file_path);	
			} catch (Exception $e) {
				$filename = null;
			}
		}
		return array($file_path, $filename);
	}
	
	/**
	 * Gets the url of a themed file
	 * @param string $file_path
	 *  Basically the subpath of the file underneath the web or theme directory
	 */
	static function themedUrl($file_path)
	{
		list($url, $filename) = self::themedUrlAndFilename($file_path);
		return $url;
	}
	
	/**
	 * Gets the filename of a themed file
	 * @param string $file_path
	 *  Basically the subpath of the file underneath the web or theme directory
	 */
	static function themedFilename($file_path)
	{
		list($url, $filename) = self::themedUrlAndFilename($file_path);
		return $filename;
	}
	
	/**
	 * Truncates some text to a length, returns result
	 * @param string $text
	 *  The text to truncate
	 * @param int $length
	 *  Length to truncate to. Defaults to 20
	 * @param string $replace
	 *  String to replace truncated text. Defaults to three dots.
	 * @param int $last_word_max_length
	 *  Defaults to 0. The maximum length of the last word.
	 *  If a positive number, adds the last word up to this length,
	 *  truncating the text before it.
	 * @param string $guarantee_result_length
	 *  Defaults to true. If true, then the result will definitely
	 *  have a string length <= $length. If false, then it might
	 *  be longer, as the length of $replace is not factored in.
	 * @return string
	 */
	static function truncate(
		$text, 
		$length = 20, 
		$replace = '...',
		$last_word_max_length = 0,
		$guarantee_result_length = true)
	{
		$text_len = strlen($text);
		$replace_len = strlen($replace);
		if ($text_len <= $length)
			return $text;
			
		if ($last_word_max_length > $text_len)
			$last_word_max_length = 0;
		$length_to_use = $length;
		if ($text_len > $length and $guarantee_result_length)
			 $length_to_use = $length - $replace_len;
		$last_word_len = 0;
		if ($last_word_max_length > 0) {
			$last_word_starts_at = strrpos($text, ' ', -2) + 1;
			$last_word_len = ($last_word_starts_at !== false)
				? $text_len - $last_word_starts_at
				: $text_len;
			if ($last_word_len > $last_word_max_length)
				$last_word_len = $last_word_max_length;
		}
		
		$text_truncated = substr($text, 0, $length_to_use - $last_word_len);
		if ($text_len > $length)
			$text_truncated .= $replace;
		if ($last_word_len)
			$text_truncated .= substr($text, -$last_word_len);
		return $text_truncated;
	}
		
	/**
	 * The theme url to be used in various methods of this class.
	 * @var string
	 */
	protected static $theme_url = null;
	
	/**
	 * The cascade of theme urls
	 * @var string
	 */
	protected static $theme_urls = array(null);
	
	/**
	 * The id prefix to be prepended to all ids passed in
	 * @var string
	 */
	protected static $id_prefix = null;
	
	/**
	 * The stack of id prefixes
	 * @var string
	 */
	protected static $id_prefixes = array(null);
	
	/**
	 * Information about the id prefixes
	 * @var string
	 */
	protected static $id_prefixes_extra = array(null);

}
