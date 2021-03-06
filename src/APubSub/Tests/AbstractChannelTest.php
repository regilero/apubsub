<?php

namespace APubSub\Tests;

use APubSub\CursorInterface;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;

abstract class AbstractChannelTest extends AbstractBackendBasedTest
{
    public function testChannelCreation()
    {
        $channel = $this->backend->createChannel('foo');
        $loaded  = $this->backend->getChannel('foo');

        $this->assertSame($channel->getId(), 'foo');
        $this->assertSame($loaded->getId(), 'foo');

        // Test normal behavior (disallow accidental creation)
        try {
            $this->backend->createChannel('foo');

            $this->fail("Should have caught a ChannelAlreadyExistsException");
        } catch (ChannelAlreadyExistsException $e) {
            $this->assertTrue(true, "Caught a ChannelAlreadyExistsException");
        }

        // Test the ignore error boolean
        try {
            $chan1 = $this->backend->createChannel('foo', true);

            $this->assertSame($chan1->getId(), $channel->getId());

            $this->assertTrue(true, "Did not caught a ChannelAlreadyExistsException");
        } catch (ChannelAlreadyExistsException $e) {
            $this->fail("Should not have caught a ChannelAlreadyExistsException");
        }

        // Test multiple creation, normal scenario
        $chanNames = array(
            'paper',
            'cisors',
            'rock',
        );
        $channelList = $this->backend->createChannels($chanNames);
        $this->assertCount(3, $channelList);

        // Test multiple creation with one existing, none should be created
        $chanNames = array(
            'chair',
            'banana',
            'cisors', // Already exists, see upper
            'sisters',
        );
        try {
            $this->backend->createChannels($chanNames);
            $this->fail("Should have caught a ChannelAlreadyExistsException");
        } catch (ChannelAlreadyExistsException $e) {
            $this->assertTrue(true, "Caught a ChannelAlreadyExistsException");
        }
        // Ensure none exist
        foreach (array('chair', 'banana', 'sisters') as $id) {
            try { 
                $channel = $this->backend->getChannel($id);
                $this->fail("Should have caught a ChannelDoesNotExistException");
            } catch (ChannelDoesNotExistException $e) {
                $this->assertTrue(true, "Caught a ChannelDoesNotExistException");
            }
        }

        // Now the same test with error ignored
        $channelList = $this->backend->createChannels($chanNames, true);
        $this->assertCount(4, $channelList);
        // Ensure all exist
        foreach (array('chair', 'banana', 'cisors', 'sisters') as $id) {
            $channel = $this->backend->getChannel($id);
            // Just for fun, this won't hurt you
            $this->assertSame($channel->getId(), $id);
        }
    }

    public function testMessageCreation()
    {
        $channel  = $this->backend->createChannel('bar');
        $contents = array('test' => 12);

        $message = $channel->send($contents);

        $this->assertSame($contents, $message->getContents());
    }

    public function testMessageSendToSubscriber()
    {
        $channel  = $this->backend->createChannel('baz');
        $contents = array('test' => 12);

        $subscriber = $channel->subscribe();
        $this->assertNotNull($subscriber->getId());

        $subscriber->activate();

        $message = $channel->send($contents);

        $id = $message->getId();

        $messages = $subscriber->fetch();
        $this->assertNotEmpty($messages);
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);

        foreach ($messages as $fetched) {
            $this->assertSame($contents, $fetched->getContents());
            $this->assertSame($id, $fetched->getId());
            break; // Only test the first
        }
    }

    public function testDelete()
    {
        // Create a channel and populate with some junk
        $channel = $this->backend->createChannel("delete_me");
        $sub1 = $channel->subscribe();
        $sub1->activate();
        $sub1Id = $sub1->getId();
        $msg1 = $channel->send(1);
        $sub2 = $channel->subscribe();
        $sub2->activate();
        $msg2 = $channel->send(2);

        // Ok, first of all, deletion
        $this->backend->deleteChannel("delete_me");

        try {
            $oldChannel = $this->backend->getChannel("delete_me");
            $this->fail("Should have caught a ChannelDoesNotExistException");
        } catch (ChannelDoesNotExistException $e) {
            $this->assertTrue(true, "Caught a ChannelDoesNotExistException");
        }

        try {
            $oldSub1 = $this->backend->getSubscription($sub1Id);
            $this->fail("Should have caught a SubscriptionDoesNotExistException");
        } catch (SubscriptionDoesNotExistException $e) {
            $this->assertTrue(true, "Caught a SubscriptionDoesNotExistException");
        }
    }

    public function testDeleteMessages()
    {
        $channel = $this->backend->createChannel('some_channel');

        // One left untouched, the other will delete a message
        $sub1    = $channel->subscribe();
        $sub2    = $channel->subscribe();

        // And also test with subscriber for coverage purpose
        $suber   = $this->backend->getSubscriber('foo');
        $suber->subscribe('some_channel');
        $sub3    = $suber->getSubscriptionFor('some_channel');

        $sub1->activate();
        $sub2->activate();
        $sub3->activate();

        $channel->send(42);
        $channel->send(13);
        $channel->send(11);
        $messageId = null;

        $cursor = $sub1->fetch();
        $cursor->addSort(CursorInterface::FIELD_MSG_SENT, CursorInterface::SORT_ASC);
        $this->assertCount(3, $cursor, "Sub 1 has 3 messages");
        foreach ($cursor as $message) {
            // There should be only one
            $this->assertSame(42, $message->getContents(), "First message value is 42");
            $messageId = $message->getId();
            break;
        }

        $sub2->deleteMessage($messageId);

        $cursor = $sub2->fetch();
        $this->assertCount(2, $cursor, "Sub 2 has 2 messages after delete");
        $cursor = $sub1->fetch();
        $this->assertCount(3, $cursor, "Sub 1 is still full");
        $cursor = $suber->fetch();
        $this->assertCount(3, $cursor, "Sub 3 is still full");

        $channel->deleteMessage($messageId);

        $cursor = $sub1->fetch();
        $this->assertCount(2, $cursor, "Sub 1 has now 2 messages");
        $cursor = $sub2->fetch();
        $this->assertCount(2, $cursor, "Sub 2 still has 2 messages");
        $cursor = $suber->fetch();
        $this->assertCount(2, $cursor, "Sub 3 has now 2 messages");
    }
}
