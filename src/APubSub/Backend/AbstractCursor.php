<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\CursorInterface;

/**
 * Base implementation for cursor, suitable for most implementations
 */
abstract class AbstractCursor extends AbstractObject implements
    \IteratorAggregate,
    CursorInterface
{
    /**
     * @var array
     */
    private $run = false;

    /**
     * @var array
     */
    private $sorts = array();

    /**
     * @var int
     */
    private $limit = CursorInterface::LIMIT_NONE;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $count = null;

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     */
    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::addSort()
     */
    final public function addSort($sort, $direction = CursorInterface::SORT_ASC)
    {
        if ($this->run) {
            throw new \LogicException("Query has already been run");
        }

        $allowed = $this->getAvailableSorts();

        if (!in_array($sort, $allowed)) {
            throw new \InvalidArgumentException("Sort field is not supported");
        }

        $this->sorts[$sort] = $direction;

        return $this;
    }

    /**
     * Get set sorts
     *
     * @return array Sort fields keys are field name while values are order
     *               direction
     */
    final public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::setLimit()
     */
    final public function setLimit($limit)
    {
        if ($this->run) {
            throw new \LogicException("Query has already been run");
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * Get limit
     *
     * @return int Limit
     */
    final public function getLimit()
    {
        return $this->limit;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::setOffset()
     */
    final public function setOffset($offset)
    {
        if ($this->run) {
            throw new \LogicException("Query has already been run");
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::setOffset()
     */
    final public function setRange($limit, $offset)
    {
        if ($this->run) {
            throw new \LogicException("Query has already been run");
        }

        $this->limit  = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get offset
     *
     * @return int Offset
     */
    final public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Set the "has run" state of this object, locking it for modification
     */
    final protected function setHasRun()
    {
        $this->run = true;
    }

    /**
     * Tell if this object internal query has run
     *
     * @return boolean True if the query has run
     */
    final protected function hasRun()
    {
        return $this->run;
    }
}
