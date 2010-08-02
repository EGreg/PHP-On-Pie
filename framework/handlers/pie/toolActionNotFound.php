<?php

/**
 * Default pie/toolActionNotFound handler.
 */
function pie_toolActionNotFound($params)
{
	header("HTTP/1.0 404 Not Found");
	echo json_encode("The tool and action were not found.");
}
