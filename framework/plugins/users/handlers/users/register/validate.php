<?php

function users_register_validate()
{
	foreach (array('email_address', 'username', 'icon') as $field) {
		if (!isset($_REQUEST[$field])) {
			throw new Pie_Exception("$field is missing", array($field));
		}
	}
}