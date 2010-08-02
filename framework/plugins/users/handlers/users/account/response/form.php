<?php

function users_account_response_form()
{
        // Calling this will fill the slots
        Pie::tool('users/account', array('_form_static' => true), array('inner' => true));
        return true;
}
