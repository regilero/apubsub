<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\ArrayCursor;
use APubSub\CursorInterface;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\PubSubInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemoryPubSub extends AbstractObject implements PubSubInterface
{
    /**
     * Sort helper for messages
     *
     * @param MemorySubscriber $a Too lazy to comment
     * @param array $conditions   Too lazy to comment
     *
     * @return bool               Too lazy to comment
     *
    public static function filterSubscribers(MemorySubscriber $a, array $conditions)
    {
        foreach ($conditions as $key => $value) {

            $value = null;

            switch ($key) {

                case CursorInterface::FIELD_SUBER_ACCESS:
                    $value = $a->isUnread();
                    break;

                case CursorInterface::FIELD_SUBER_NAME:
                    $value = $a->isUnread();
                    break;
            }

            if (null === $key && $value !== null || $key != $value) {
                return false;
            }
        }

        return true;
    }
     */

    public function __construct()
    {
        $this->context = new MemoryContext($this);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::setOptions()
     */
    public function setOptions(array $options)
    {
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannel()
     */
    public function getChannel($id)
    {
        if (!isset($this->context->channels[$id])) {
            throw new ChannelDoesNotExistException();
        }

        return $this->context->channels[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannels()
     */
    public function getChannels(array $idList)
    {
        $ret = array();

        foreach ($idList as $chanId) {
            $ret[] = $this->getChannel($chanId);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannel()
     */
    public function createChannel($id, $ignoreErrors = false)
    {
        if (isset($this->context->channels[$id])) {
            if ($ignoreErrors) {
                return $this->getChannel($id);
            } else {
                throw new ChannelAlreadyExistsException();
            }
        } else {
            return $this->context->channels[$id] = new MemoryChannel($this->context, $id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannels()
     */
    public function createChannels($idList, $ignoreErrors = false)
    {
        $ret = array();

        if ($ignoreErrors) {
            foreach ($idList as $id) {
                $ret[] = $this->createChannel($id, true);
            }
        } else {
            $existing = array_intersect_key(array_flip($idList), $this->context->channels);

            if (empty($existing)) {
                foreach ($idList as $id) {
                    // They are not supposed to exist
                    $ret[] = $this->createChannel($id, true);
                }
            } else {
                throw new ChannelAlreadyExistsException();
            }
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        $channel = $this->getChannel($id);

        foreach ($this->context->subscriptions as $index => $subscription) {
            if ($subscription->getChannel()->getId() === $id) {
                unset($this->context->subscriptions[$index]);
            }
        }
        $this->context->subscriptions = array_filter($this->context->subscriptions);

        unset($this->context->channels[$id]);
        unset($this->context->channelMessages[$id]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscription()
     */
    public function getSubscription($id)
    {
        if (!isset($this->context->subscriptions[$id])) {
            throw new SubscriptionDoesNotExistException();
        }

        return $this->context->subscriptions[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscriptions()
     */
    public function getSubscriptions($idList)
    {
        $ret = array();

        foreach ($idList as $id) {
            $ret[] = $this->getSubscription($id);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscription()
     */
    public function deleteSubscription($id)
    {
        $this->getSubscription($id);

        unset($this->context->subscriptions[$id]);
        unset($this->context->subscriptionMessages[$id]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscriptions()
     */
    public function deleteSubscriptions($idList)
    {
        foreach ($idList as $id) {
            $this->deleteSubscription($id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::fetchSubscribers()
     */
    public function fetchSubscribers(array $conditions = null)
    {
        throw new \Exception("Not implemented yet");

        $ret = $this->context->subscribers;

        if ($conditions) {
            $ret = array_filter($ret, function ($a) use ($conditions) {
                return MemoryPubSub::filterSubscribers($a, $conditions);
            });
        }

        $sorter = new MemorySubscriberSorter();

        return new ArrayCursor($this->context, $ret, $sorter->getAvailableSorts(), $sorter);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscriber()
     */
    public function getSubscriber($id)
    {
        if (!isset($this->context->subscribers[$id])) {
            $this->context->subscribers[$id] = new MemorySubscriber($this->context, $id);
        }

        return $this->context->subscribers[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::flushCaches()
     */
    public function flushCaches()
    {
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::garbageCollection()
     */
    public function garbageCollection()
    {
    }

    public function getAnalysis()
    {
        // This is a pure implementation sample, there is no way you would ever
        // try to run this on a production environment
        return array(
            "Number of chans" => count($this->context->channels),
            "Number of subscribers" => count($this->context->subscribers),
            "Number of subscriptions" => count($this->context->subscriptions),
        );
    }
}
