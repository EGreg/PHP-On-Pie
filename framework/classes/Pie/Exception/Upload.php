<?php

class Pie_Exception_ToolAlreadyRendered extends Pie_Exception
{
	function __construct($params = array(), $input_fields = array())
	{
		parent::__construct($params, $input_fields);
		if (!isset($params['code'])) {
			return;
		}
		switch ($params['code']) {
		case UPLOAD_ERR_INI_SIZE:
			$this->message = "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$this->message = "Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
			break;
		case UPLOAD_ERR_PARTIAL:
			$this->message = "Value: 3; The uploaded file was only partially uploaded.";
			break;
		case UPLOAD_ERR_NO_FILE:
			$this->message = "Value: 4; No file was uploaded.";
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$this->message = "Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.";
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$this->message = "Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.";
			break;
		case UPLOAD_ERR_EXTENSION:
			$this->message = "A PHP extension stopped the file upload.";
			break;
	}
};

Pie_Exception::add('Pie_Exception_ToolAlreadyRendered', 'Upload error');
