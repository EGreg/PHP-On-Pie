<?php

/**
 * Determine and return whether the account is complete
 * Override this handler in your apps to determine
 * whether an account is considered complete.
 *
 * @return bool
 */
function users_account_complete($params)
{
	$missing = Users::accountStatus($email);
	$complete = !is_array($missing) or empty($missing);
	return $complete;
}
