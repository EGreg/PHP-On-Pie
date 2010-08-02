<?php

function users_account_response_static()
{
        // Calling this will fill the slots
        Pie::tool('users/account', array('_form_static' => true), array('inner' => true));
        return true;
}


