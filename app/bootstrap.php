<?php

require_once __DIR__ . '/../vendor/dibi.min.php';
require_once __DIR__ . '/../vendor/LeanMapper/loader.php';

require_once __DIR__ . '/Model/Entity/Person.php';
require_once __DIR__ . '/Model/Entity/Author.php';
require_once __DIR__ . '/Model/Entity/Borrower.php';
require_once __DIR__ . '/Model/Entity/Book.php';
require_once __DIR__ . '/Model/Entity/Borrowing.php';
require_once __DIR__ . '/Model/Entity/Tag.php';

require_once __DIR__ . '/Model/Repository/Repository.php';
require_once __DIR__ . '/Model/Repository/AuthorRepository.php';
require_once __DIR__ . '/Model/Repository/BookRepository.php';
require_once __DIR__ . '/Model/Repository/BorrowerRepository.php';
require_once __DIR__ . '/Model/Repository/TagRepository.php';

function write($value, $indent = 0) {
	echo str_repeat(' ', $indent), $value, "\n";
}

function separate() {
	echo "\n-----\n\n";
}

function superscribe($heading) {
	echo $heading . "\n", str_repeat('=', mb_strlen($heading, 'utf8')) . "\n\n";
}

$connection = new DibiConnection(array(
	'driver' => 'sqlite3',
	'database' => __DIR__ . '/../db/quickstart.sq3',
));

header('Content-type: text/plain;charset=utf8');
