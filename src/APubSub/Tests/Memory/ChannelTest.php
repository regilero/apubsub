<?php

namespace APubSub\Tests\Memory;

use APubSub\Backend\Memory\MemoryPubSub;
use APubSub\Tests\AbstractChannelTest;

class ChannelTest extends AbstractChannelTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
