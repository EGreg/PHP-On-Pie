#!/usr/bin/env php
<?php

/**
 * This script is for testing your code
 */

echo 'u'; exit;
include(dirname(__FILE__) . '/../pie.php');
Pie::test("tests/smoke/*/*TestCase.php");
exit;

$argv = $_SERVER['argv'];
$count = count($argv);

$usage = <<<EOT
Usage: php {$argv[0]} \$app_local_dir \$filename_pattern

\$app_local_dir - path of the 'local' folder of your app

\$filename_pattern - the filename pattern that
  matches all the test case files, i.e. FooTestCase.php
  This is relative to the tests directory.
  Example, smoke tests:   before_commit/*.php
  Example, all tests:     */*.php


EOT;

if ($count < 3) {
	echo $usage;
	exit;
}

$local_dir = $argv[1];
$filename_pattern = $argv[2];
include_once('script_header.inc.php');

$tests_dir = realpath(PAL_PROJECT_DIR . DS . 'tests');
$test_files = glob($tests_dir . DS . $filename_pattern);

$results = array();
foreach ($test_files as $tf) {
	$b = basename($tf);
	if (strtolower(substr($b, -12)) != 'testcase.php') {
		// This is not a test case file
		continue;
	}
	$cn = substr($b, 0, -4);
	include_once($tf);
	if (!class_exists($cn)) {
		echo "SKIPPING: class $cn not defined in $b.\n";
		continue;
	}
	$tc = new $cn;
	if (!($tc instanceof PalTestCase)) {
		echo "SKIPPING: $cn does not extend PalTestCase.\n";
		continue;
	}
	$results = array_merge($results, $tc->run());
}

ksort($results);

// Report (for now)
$cutoff = 10;
$last_reported_cn = '';
foreach ($results as $cn => $result) {
	ksort($result);
	foreach ($result as $tn => $r) {
		if ($r[0] >= $cutoff) {
			if ($last_reported_cn != $cn) {
				echo "$cn had problems:\n";
				$last_reported_cn = $cn;
			}
			$t2 = str_pad("$tn", 30, ' ');
			$m = $r[1];
			$c = "X";
			switch ($r[0]) {
				case PalTestCaseException::TEST_SUCCEEDED:
					$c = '    ';
					break;
				case PalTestCaseException::TEST_INCOMPLETE:
					$c = 'i';
					break;
				case PalTestCaseException::TEST_SKIPPED:
					$c = 's';
					break;
				case PalTestCaseException::TEST_FAILED:
					$c = 'f';
					break;
				case PalTestCaseException::TEST_EXCEPTION:
					$c = 'e (' . $r[2]->getCode() . ')';
					$m = $r[2]->getMessage();
					break;
			}
			echo "  $t2\t$c $m\n";
		}
	}
}
