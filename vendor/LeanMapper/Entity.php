<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper;

use Closure;
use DibiConnection;
use DibiFluent;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidValueException;
use LeanMapper\Exception\MemberAccessException;
use LeanMapper\Exception\RuntimeException;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use LeanMapper\Relationship;
use LeanMapper\Row;

/**
 * Base class for custom entities
 *
 * @author Vojtěch Kohout
 */
abstract class Entity
{

	/** @var Row */
	protected $row;

	/** @var EntityReflection[] */
	protected static $reflections = array();


	/**
	 * @param Row|null $row
	 */
	public function __construct(Row $row = null)
	{
		$this->row = $row !== null ? $row : Result::getDetachedInstance()->getRow();
	}

	/**
	 * Returns value of given field
	 *
	 * @param string $name
	 * @return mixed
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 * @throws RuntimeException
	 */
	public function __get($name/*, array $filterArgs*/)
	{
		$property = $this->getReflection()->getEntityProperty($name);
		if ($property === null) {
			$method = 'get' . ucfirst($name);
			if (method_exists($this, $method)) { // TODO: find better solution (using reflection)
				return call_user_func(array($this, $method)); // $filterArgs are not relevant here
			}
			throw new MemberAccessException("Undefined property: $name");
		}
		$column = $property->getColumn();
		if ($property->isBasicType()) {
			$value = $this->row->$column;
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property '$name' cannot be null.");
				}
				return null;
			}
			if (!settype($value, $property->getType())) {
				throw new InvalidValueException("Cannot convert value '$value' to " . $property->getType() . '.');
			}
		} else {
			if ($property->hasRelationship()) {

				$filter = null;
				$callbacks = $property->getFilters();
				if (!empty($callbacks)) {
					$args = func_get_args();
					$filterArgs = isset($args[1]) ? $args[1] : array();
					$filter = function (DibiFluent $statement) use ($callbacks, $filterArgs) {
						foreach ($callbacks as $callback) {
							call_user_func_array($callback, array_merge(array($statement), $filterArgs));
						}
					};
				}
				$relationship = $property->getRelationship();

				$method = explode('\\', get_class($relationship));
				$method = 'get' . array_pop($method) . 'Value';
				$value = $this->$method($property, $filter);

			} else {
				$value = $this->row->$column;
				$actualClass = get_class($value);
				if ($value === null) {
					if (!$property->isNullable()) {
						throw new InvalidValueException("Property '$name' cannot be null.");
					}
					return null;
				}
				if (!$property->containsCollection()) {
					if ($actualClass !== $property->getType()) {
						throw new InvalidValueException("Property '$name' is expected to contain an instance of '{$property->getType()}', instance of '$actualClass' given.");
					}
				} else {
					if (!is_array($value)) {
						throw new InvalidValueException("Property '$name' is expected to contain an array of '{$property->getType()}' instances.");
					}
				}
			}
		}
		return $value;
	}

	/**
	 * Sets value of given field
	 *
	 * @param string $name
	 * @param mixed $value
	 * @throws InvalidMethodCallException
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 */
	function __set($name, $value)
	{
		$property = $this->getReflection()->getEntityProperty($name);
		if ($property === null) {
			$method = 'set' . ucfirst($name);
			if (method_exists($this, $method)) { // TODO: find better solution (using reflection)
				call_user_func(array($this, $method), $value);
			} else {
				throw new MemberAccessException("Undefined property: $name");
			}
		} else {
			if (!$property->isWritable()) {
				throw new MemberAccessException("Cannot write to read only property '$name'.");
			}
			$column = $property->getColumn();
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property '$name' cannot be null.");
				}
				$this->row->$column = null;
			} else {
				if ($property->isBasicType()) {
					if (!settype($value, $property->getType())) {
						throw new InvalidValueException("Cannot convert value '$value' to " . $property->getType() . '.');
					}
					$this->row->$column = $value;
				} else {
					if ($property->hasRelationship()) {
						if (!($value instanceof Entity)) {
							throw new InvalidValueException("Only entites can be set via magic __set on field with relationships.");
						}
						$relationship = $property->getRelationship();
						if (!($relationship instanceof Relationship\HasOne)) {
							throw new InvalidMethodCallException('Only fields with m:hasOne relationship can be set via magic __set.');
						}
						$column = $relationship->getColumnReferencingTargetTable();
						$table = $relationship->getTargetTable();

						if ($value->isDetached()) {
							throw new InvalidValueException('Detached entity must be stored in database before use in relationships.');
						}
						$this->row->$column = $value->id;
						$this->row->cleanReferencedRowsCache($table, $column);
					} else {
						if (!is_object($value)) {
							throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . gettype($value) . " given.");
						}
						if (get_class($value) !== $property->getType()) {
							throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . get_class($value) . " given.");
						}
						$this->row->$column = $value;
					}
				}
			}
		}
	}

	/**
	 * Calls __get() or __set() method when get<$name> or set<$name> methods don't exist
	 *
	 * @param string $name
	 * @param array $arguments
	 * @param array $arguments
	 * @return mixed
	 * @throws InvalidMethodCallException
	 */
	public function __call($name, array $arguments)
	{
		$e = new InvalidMethodCallException("Method '$name' is not callable.");
		if (strlen($name) < 4) {
			throw $e;
		}
		$prefix = substr($name, 0, 3);
		if ($prefix === 'get') {
			return $this->__get(lcfirst(substr($name, 3)), $arguments);
		} elseif ($prefix === 'set') {
			$this->__set(lcfirst(substr($name, 3)), $arguments);
		} else {
			throw $e;
		}
	}

	/**
	 * Performs a mass value assignment (using setters)
	 *
	 * @param array $values
	 * @param array|null $whitelist
	 */
	public function assign(array $values, array $whitelist = null)
	{
		if ($whitelist !== null) {
			$whitelist = array_flip($whitelist);
		}
		foreach ($values as $field => $value) {
			if ($whitelist === null or isset($whitelist[$field])) {
				$this->__set($field, $value);
			}
		}
	}

	/**
	 * Tells whether entity is in modified state
	 *
	 * @return bool
	 */
	public function isModified()
	{
		return $this->row->isModified();
	}

	/**
	 * Tells whether entity is in detached state (like newly created entity)
	 *
	 * @return bool
	 */
	public function isDetached()
	{
		return $this->row->isDetached();
	}

	/**
	 * Marks entity as detached (it means non-persisted)
	 */
	public function detach()
	{
		$this->row->detach();
	}

	/**
	 * Returns array of modified fields with new values
	 *
	 * @return array
	 */
	public function getModifiedData()
	{
		return $this->row->getModifiedData();
	}

	/**
	 * Marks entity as non-updated (isModified() returns false right after this method call)
	 */
	public function markAsUpdated()
	{
		$this->row->markAsUpdated();
	}

	/**
	 * Marks entity as persisted
	 *
	 * @param int $id
	 * @param string $table
	 * @param DibiConnection $connection
	 */
	public function markAsCreated($id, $table, DibiConnection $connection)
	{
		$this->row->markAsCreated($id, $table, $connection);
	}

	////////////////////
	////////////////////

	/**
	 * @return EntityReflection
	 */
	private static function getReflection()
	{
		$class = get_called_class();
		if (!isset(static::$reflections[$class])) {
			static::$reflections[$class] = new EntityReflection($class);
		}

		return static::$reflections[$class];
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return mixed
	 * @throws InvalidValueException
	 */
	private function getHasOneValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$row = $this->row->referenced($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingTargetTable());
		if ($row === null) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}
			return null;
		} else {
			$class = $property->getType();
			return new $class($row);
		}
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return array
	 */
	private function getHasManyValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getRelationshipTable(), null, $relationship->getColumnReferencingSourceTable());
		$class = $property->getType();
		$value = array();
		foreach ($rows as $row) {
			$valueRow = $row->referenced($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingTargetTable());
			if ($valueRow !== null) {
				$value[] = new $class($valueRow);
			}
		}
		return $value;
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return mixed
	 * @throws InvalidValueException
	 */
	private function getBelongsToOneValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingSourceTable());
		$count = count($rows);
		if ($count > 1) {
			throw new InvalidValueException('There cannot be more than one entity referencing to entity with m:belongToOne relationship.');
		} elseif ($count === 0) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}
			return null;
		} else {
			$row = reset($rows);
			$class = $property->getType();
			return new $class($row);
		}
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return array
	 */
	private function getBelongsToManyValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingSourceTable());
		$class = $property->getType();
		$value = array();
		foreach ($rows as $row) {
			$value[] = new $class($row);
		}
		return $value;
	}
	
}
