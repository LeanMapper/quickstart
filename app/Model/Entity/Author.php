<?php

namespace Model\Entity;

/**
* @property Book[] $books m:belongsToMany
* @property Book[] $reviewedBooks m:belongsToMany(reviewer_id)
* @property string|null $web
*/
class Author extends Person
{

	public function getReferencingTags()
	{
		$tags = array();
		foreach (array(null, 'reviewer_id') as $viaColumn) {
			foreach ($this->row->referencing('book', null, $viaColumn) as $book) {
				foreach ($book->referencing('book_tag') as $tagRelation) {
					$row = $tagRelation->referenced('tag');
					$tags[$tagRelation->tag_id] = new Tag($row);
				}
			}
		}
		return $tags;
	}

}
