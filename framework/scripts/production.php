<?php

/**
 * This script prepares your code for production,
 * making it faster and more efficient.
 */

include(dirname(__FILE__) . '/../pie.php');

Pie::event('pie/production/aggregate_classes');
Pie::event('pie/production/aggregate_handlers');
Pie::event('pie/production/comments');
