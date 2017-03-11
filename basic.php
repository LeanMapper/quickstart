<?php

use Model\Repository\BookRepository;
use Model\Repository\AuthorRepository;

require_once __DIR__ . '/app/bootstrap.php';

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);
$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

$books = $bookRepository->findAll();

superscribe('Přehled knih a výpůjček');

foreach ($books as $book) {
	write($book->name);
	write('Autor: ' . $book->author->name);
	write('Výpůjčky:');
	foreach ($book->borrowings as $borrowing) {
		write($borrowing->borrower->name . '(' . $borrowing->date . ')', 3);
	}
	separate();
}

superscribe('Autorství a recenzenství');

$authors = $authorRepository->findAll();

foreach ($authors as $author) {
	write($author->name);
	write('Je autorem:');
	foreach ($author->books as $book) {
		write($book->name, 3);
	}
	write('Recenzoval:');
	foreach ($author->reviewedBooks as $book) {
		write($book->name, 3);
	}
	separate();
}

superscribe('Tagy, které souvisí s autory');

foreach ($authors as $author) {
	write($author->name);
	foreach ($author->getReferencingTags() as $tag) {
		write($tag->name, 3);
	}
	separate();
}
