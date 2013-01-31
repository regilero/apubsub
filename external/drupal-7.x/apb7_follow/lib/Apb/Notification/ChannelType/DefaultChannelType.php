<?php

namespace Apb\Notification\ChannelType;

use Apb\Notification\ChannelTypeInterface;

/**
 * Default implementation as a null implementation.
 */
class DefaultChannelType implements ChannelTypeInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $description;

    /**
     * @var boolean
     */
    private $isVisible;

    public function __construct($type, $description, $isVisible = true)
    {
        $this->type        = $type;
        $this->description = $description;
        $this->isVisible   = $isVisible;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::getType()
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::getDescription()
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::isVisible()
     */
    public function isVisible()
    {
        return $this->isVisible;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::getSubscriptionLabel()
     */
    public function getSubscriptionLabel($id)
    {
        if (null === $this->description) {
            return $this->type . ' #' . $id;
        } else {
            return $this->description. ' #' . $id;
        }
    }
}
