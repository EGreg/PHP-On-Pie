<?php

function pie_response_notices()
{
	$errors = Pie_Response::getErrors();
	if (empty($errors)) {
		return '';
	}
	$result = "<ul class='pie_errors'>";
	foreach ($errors as $e) {
		$result .= "<li>".$e->getMessage()."</li>";
	}
	return $result .= "</ul>";
}
