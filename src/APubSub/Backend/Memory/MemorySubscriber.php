<?php

namespace APubSub\Backend\Memory;

use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\SubscriberInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemorySubscriber extends AbstractMemoryObject implements
    SubscriberInterface
{
    /**
     * User set identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Current subscriptions
     *
     * @var array
     */
    private $subscriptions = array();

    /**
     * Default constructor
     *
     * @param MemoryContext $context Context
     * @param scalar $id             Identifier
     */
    public function __construct(MemoryContext $context, $id)
    {
        $this->id = $id;
        $this->context = $context;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptions()
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptionFor()
     */
    public function getSubscriptionFor($channelId)
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getChannel()->getId() === $channelId) {
                return $subscription;
            }
        }

        throw new SubscriptionDoesNotExistException();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::subscribe()
     */
    public function subscribe($channelId)
    {
        $channel = $this->context->backend->getChannel($channelId);

        // Remember, this is for testing purposes only
        try {
            if ($this->getSubscriptionFor($channelId)) {
                throw new SubscriptionAlreadyExistsException();
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // Everything is OK
        }

        $subscription = $channel->subscribe();
        $subscription->activate();

        $this->subscriptions[$subscription->getId()] = $subscription;

        return $subscription;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetch()
     */
    public function fetch()
    {
        $ret = array();

        foreach ($this->getSubscriptions() as $subscription) {
            $ret = array_merge($subscription->fetch(), $ret);
        }

        uasort($ret, function ($m1, $m2) {
            return $m1->getId() - $m2->getId();
        });

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::flush()
     */
    public function flush()
    {
        foreach ($this->getSubscriptions() as $subscription) {
            $subscription->flush();
        }
    }
}
