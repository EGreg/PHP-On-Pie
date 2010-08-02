<?php

function users_contact_response_content()
{
        Pie_Session::start();
        return Pie::tool('users/contact');
}
