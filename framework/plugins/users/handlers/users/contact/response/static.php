<?php

function users_contact_response_static()
{
        // Calling this will fill the slots
        Pie::tool('users/contact', array('_form_static' => true), array('inner' => true));
        return true;
}
