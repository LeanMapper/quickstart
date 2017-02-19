<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper\Relationship;

/**
 * Has many relationship
 *
 * @author Vojtěch Kohout
 */
class HasMany
{

	/** @var string */
	private $columnReferencingSourceTable;

	/** @var string */
	private $relationshipTable;

	/** @var string */
	private $columnReferencingTargetTable;

	/** @var string */
	private $targetTable;


	/**
	 * @param string $columnReferencingSourceTable
	 * @param string $relationshipTable
	 * @param string $columnReferencingTargetTable
	 * @param string $targetTable
	 */
	public function __construct($columnReferencingSourceTable, $relationshipTable, $columnReferencingTargetTable, $targetTable)
	{
		$this->columnReferencingSourceTable = $columnReferencingSourceTable;
		$this->relationshipTable = $relationshipTable;
		$this->columnReferencingTargetTable = $columnReferencingTargetTable;
		$this->targetTable = $targetTable;
	}

	/**
	 * Returns name of column referencing source table
	 *
	 * @return string
	 */
	public function getColumnReferencingSourceTable()
	{
		return $this->columnReferencingSourceTable;
	}

	/**
	 * Returns name of relationship table
	 *
	 * @return string
	 */
	public function getRelationshipTable()
	{
		return $this->relationshipTable;
	}

	/**
	 * Returns name of column referencing target table
	 *
	 * @return string
	 */
	public function getColumnReferencingTargetTable()
	{
		return $this->columnReferencingTargetTable;
	}

	/**
	 * Returns name of target table
	 *
	 * @return string
	 */
	public function getTargetTable()
	{
		return $this->targetTable;
	}

}