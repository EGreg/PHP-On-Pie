<?php

function myApp_notFound_response_content($params)
{
        header("HTTP/1.0 404 Not Found");
        $url = Pie_Request::url();
        return Pie::view("myApp/content/notFound.php", compact('url'));
}

