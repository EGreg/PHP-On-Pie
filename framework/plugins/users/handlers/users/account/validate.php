<?php

function users_account_validate()
{
        $birthday_year = $birthday_month = $birthday_day = null;
        extract($_REQUEST);

		/*
        $field_names = array(
                'first_name' => 'First name',
                'last_name' => 'Last name',
                'username' => 'Username',
                'gender' => 'Your gender',
                'desired_gender' => 'Gender preference',
                'orientation' => 'Orientation',
                'relationship_status' => 'Status',
                'zipcode' => 'Zipcode'
        );
        foreach ($field_names as $name => $label) {
                if (isset($_POST[$name]) and !($_POST[$name])) {
                        Pie_Response::addError(
                                new Pie_Exception_RequiredField(array('field' => $label), $name)
                        );
                }
        };
		*/

        if (isset($birthday_year)) {
                if (!checkdate($birthday_month, $birthday_day, $birthday_year)) {
                        $field = 'Birthday';
                        $range = 'a valid date';
                        Pie_Response::addError(
                                new Pie_Exception_WrongValue(compact('field', 'range'), 'birthday')
                        );
                }
        }
        if (isset($username)) {
                try {
                     	Pie::event('users/validate/username', compact('username'));
                } catch (Exception $e) {
                        Pie_Response::addError($e);
                }
        }
}


