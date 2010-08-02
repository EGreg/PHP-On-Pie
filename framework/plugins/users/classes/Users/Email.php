<?php

/**
 * Class representing email rows.
 * You can create an object of this class either to
 * access its non-static methods, or to actually
 * represent a email row in the users database.
 *
 * This description should be revised and expanded.
 *
 * @package users
 */
class Users_Email extends Base_Users_Email
{
	/**
	 * The setUp() method is called the first time
	 * an object of this class is constructed.
	 */
	function setUp()
	{
		parent::setUp();
		// INSERT YOUR CODE HERE
		// e.g. $this->hasMany(...) and stuff like that.
	}
	
	/**
	 * @param string $subject
	 *  The subject. May contain variable references to memebrs
	 *  of the $fields array.
	 * @param string $view
	 *  The name of a view
	 * @param array $fields
	 *  The fields referenced in the subject and/or view
	 * @param array $optoins
	 *  Array of options. Can include:
	 *  "html" => Defaults to false. Whether to send as HTML email.
	 *  "name" => A human-readable name in addition to the address.
	 *  "from" => An array of email_address => human_readable_name
	 */
	function sendMessage(
		$subject,
		$view,
		$fields = array(),
		$options = array())
	{
		if (!isset($options['from'])) {
			$url_parts = parse_url(Pie_Request::baseUrl());
			$domain = $url_parts['host'];
			$options['from'] = array("email_bot@".$domain => $domain);
		} else {
			if (!is_array($options['from'])) {
				throw new Pie_Exception_WrongType(array(
					'field' => '$options["from"]',
					'type' => 'array'
				));
			}
		}
		
		// Set up the default mail transport
		$tr = new Zend_Mail_Transport_Sendmail('-f'.$this->address);
		Zend_Mail::setDefaultTransport($tr);
		
		$mail = new Zend_Mail();
		$from_name = reset($options['from']);
		$mail->setFrom(key($options['from']), $from_name);
		if (isset($options['name'])) {
			$mail->addTo($this->address, $options['name']);
		} else {
			$mail->addTo($this->address);
		}
		$subject = Pie::expandString($subject, $fields);
		$body = Pie::view($view, $fields);
		$mail->setSubject($subject);
		if (empty($options['html'])) {
			$mail->setBodyText($body);
		} else {
			$mail->setBodyHtml($body);
		}
		$mail->send();
		return true;
	}
	
	/**
	 * Implements the __set_state method, so it can work with
	 * with var_export and be re-imported successfully.
	 */
	static function __set_state(array $array) {
		$result = new Users_Email();
		foreach($array as $k => $v)
			$result->$k = $v;
		return $result;
	}
};
