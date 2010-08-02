<?php

function myApp_welcome_response_content($params)
{
        // Do controller stuff here. Prepare variables
        $tabs = array("foo" => "bar");
        $description = "this is a description";
        return Pie::view('myApp/content/welcome.php', compact('tabs', 'description'));
}

