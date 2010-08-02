<?php

function users_account_response_content()
{
        Pie_Session::start();
        return Pie::tool('users/account');
}
