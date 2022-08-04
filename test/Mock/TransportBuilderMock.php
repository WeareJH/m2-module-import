<?php

declare(strict_types=1);

namespace Jh\ImportTest\Mock;

use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\Template\TransportBuilder;

class TransportBuilderMock extends TransportBuilder
{
    /**
     * @var EmailMessage
     */
    private $sentMessage;

    protected function reset(): self
    {
        $this->sentMessage = $this->message;
        parent::reset();
        return $this;
    }

    public function getSentMessage(): EmailMessage
    {
        return $this->sentMessage;
    }

    public function getTransport(): TransportMock
    {
        $this->prepareMessage();
        $this->reset();
        return new TransportMock($this->message);
    }
}
