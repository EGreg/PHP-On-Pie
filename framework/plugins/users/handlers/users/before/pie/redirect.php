<?php

function users_before_pie_redirect($params)
{
	if (Pie_Request::accepts('text/fbml')) {
		// We are in an FBML canvas page, so redirect the facebook way
		Users::$facebook->redirect($params['url']);
		return true;
	}
}
