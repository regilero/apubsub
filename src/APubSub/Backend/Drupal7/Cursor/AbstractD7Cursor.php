<?php

namespace APubSub\Backend\Drupal7\Cursor;

use APubSub\Backend\AbstractCursor;
use APubSub\Backend\ApplyIteratorIterator;
use APubSub\CursorInterface;

/**
 * Drupal 7 base implementation of list helpers
 */
abstract class AbstractD7Cursor extends AbstractCursor implements
    \IteratorAggregate,
    CursorInterface
{
    /**
     * Over this number of asked results objects will be single loaded on
     * demand while iterating instead of being all fully loaded
     *
     * FIXME: Restore this later
     */
    const LOAD_MULTIPLE_THREESHOLD = PHP_INT_MAX;

    /**
     * @var array
     */
    private $result;

    /**
     * @var \SelectQuery
     */
    private $query;

    /**
     * @var int
     */
    private $count;

    /**
     * Get initial query (no limit, no offset, no sort):
     *   - The id field must be the first in the SELECT clause
     *   - All needed tables and JOIN statements must be set
     *
     * @return \SelectQuery Query
     */
    protected abstract function createdQuery();

    /**
     * Load single object instance
     *
     * @param mixed $id Identifier
     *
     * @return mixed    Object instance
     */
    protected abstract function loadObject($id);

    /**
     * Load list of objects instances
     *
     * @param array|Traversable $idList List of identifiers
     *
     * @return array|Traversable        List of object instances
     */
    protected abstract function loadObjects($idList);

    /**
     * Get sort column in the select query 
     *
     * @param int $sort Sort field
     */
    protected abstract function getSortColumn($sort);

    /**
     * Get query
     *
     * @return \SelectQueryInterface Drupal select query
     */
    final protected function getQuery()
    {
        if (null === $this->query) {
            $this->query = $this->createdQuery();
        }

        return $this->query;
    }

    /**
     * Run internal query and populate the internal result array
     */
    final protected function runQuery()
    {
        if (null === $this->result) {

            $limit = $this->getLimit();
            $query = $this->getQuery();

            if (CursorInterface::LIMIT_NONE !== $limit) {
                $this->query->range($this->getOffset(), $limit);
            }

            foreach ($this->getSorts() as $sort => $order) {
                $query->orderBy(
                    $this->getSortColumn($sort),
                    ($order === CursorInterface::SORT_ASC ? 'asc' : 'desc'));
            }

            $result = $query->execute();

            if (!$limit || (self::LOAD_MULTIPLE_THREESHOLD < $limit)) {
                $this->result = new ApplyIteratorIterator($result, function ($object) {
                    return $this->loadObject($object->id);
                });
            } else {
                $this->result = $this->loadObjects($result->fetchCol());
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \IteratorAggregate::getIterator()
     */
    final public function getIterator()
    {
        $this->runQuery();

        if (is_array($this->result)) {
            return new \ArrayIterator($this->result);
        } else {
            return $this->result;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    final public function count()
    {
        $this->runQuery();

        return count($this->result);
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    final public function getTotalCount()
    {
        if (null === $this->count) {

            $query = $this->getQuery();
            $query = clone $query;

            $this->count = $query
                ->countQuery()
                ->execute()
                ->fetchField();
        }

        return $this->count;
    }
}
