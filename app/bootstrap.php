<?php

require_once __DIR__ . '/../vendor/autoload.php';

function write($value, $indent = 0) {
	echo str_repeat(' ', $indent), $value, "\n";
}

function separate() {
	echo "\n-----\n\n";
}

function superscribe($heading) {
	echo $heading . "\n", str_repeat('=', mb_strlen($heading, 'utf8')) . "\n\n";
}

$connection = new LeanMapper\Connection(array(
	'driver' => 'sqlite3',
	'database' => __DIR__ . '/../db/quickstart.sq3',
));
$mapper = new LeanMapper\DefaultMapper;
$entityFactory = new LeanMapper\DefaultEntityFactory;

header('Content-type: text/plain;charset=utf8');
