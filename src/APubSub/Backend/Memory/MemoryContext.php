<?php

namespace APubSub\Backend\Memory;

use APubSub\ContextInterface;

/**
 * Context implementation for memory objects
 */
class MemoryContext implements ContextInterface
{
    /**
     * @var \APubSub\Backend\Memory\MemoryPubSub
     */
    public $backend;

    /**
     * Array of channels
     *
     * @var array
     */
    public $channels = array();

    /**
     * Array of subscriptions
     *
     * @var array
     */
    public $subscriptions = array();

    /**
     * Array of subscribers
     *
     * @var array
     */
    public $subscribers = array();

    /**
     * Ordered list of messages (order is creation time asc)
     *
     * @var array
     */
    public $messages = array();

    /**
     * Message identifiers sequence
     *
     * @var int
     */
    private $messageIdSeq = 0;

    /**
     * Subscriptions identifiers sequence
     *
     * @var int
     */
    private $subscriptionIdSeq = 0;

    /**
     * Default constructor
     *
     * @param MemoryPubSub $backend Backend
     */
    public function __construct(MemoryPubSub $backend)
    {
        $this->backend = $backend;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::getBackend()
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::setOptions()
     */
    public function setOptions($options)
    {
        // FIXME: Parse options
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::getOptions()
     */
    public function getOptions()
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Get next message identifier
     *
     * @return int
     */
    public function getNextMessageIdentifier()
    {
        return ++$this->messageIdSeq;
    }

    /**
     * Get next message identifier
     *
     * @return int
     */
    public function getNextSubscriptionIdentifier()
    {
        return ++$this->subscriptionIdSeq;
    }

    /**
     * Add a new message to queue
     *
     * @param MemoryMessage $message Message
     */
    public function addMessage(MemoryMessage $message)
    {
        $this->messages[$message->getId()] = $message;
    }

    /**
     * Filter the message list using the given subscription identifiers and
     * returns it
     *
     * Messages will be deleted from queue if all subscriptions have fetched
     * them
     *
     * @param array $idList List of subscription identifiers
     * @param int $limit    Optionally limits the number of messages fetched
     * @param bool $reverse Set this to TRUE if you want the latest messages
     */
    public function getMessageListFor(array $idList, $limit = null, $reverse = false)
    {
        $i = 0;
        $ret = array();

        if ($reverse) {
            $source = array_reverse($this->messages, true);
        } else {
            $source = $this->messages;
        }

        foreach ($source as $id => $message) {
            if (null !== $limit && ++$i <= $limit) {
                break;
            } else if ($message->hasSubscribersIn($idList)) {
                // Handle queue removal
                $message->removeSubscriptionIds($idList);
                if ($message->isConsumed()) {
                    unset($this->messages[$id]);
                }

                $ret[] = $message;
            }
        }

        return $ret;
    }
}