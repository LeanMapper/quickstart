<?php

namespace Model\Entity;

/**
 * @property int $id
 * @property Book $book m:hasOne
 * @property Borrower $borrower m:hasOne
 * @property string $date
 */
class Borrowing extends \LeanMapper\Entity
{
}
