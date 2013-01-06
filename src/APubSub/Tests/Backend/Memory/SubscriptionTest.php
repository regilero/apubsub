<?php

namespace APubSub\Tests\Backend\Memory;

use APubSub\Backend\Memory\MemoryPubSub;
use APubSub\Tests\AbstractSubscriptionTest;

class SubscriptionTest extends AbstractSubscriptionTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
