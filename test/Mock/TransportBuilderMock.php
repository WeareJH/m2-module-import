<?php

declare(strict_types=1);

namespace Jh\ImportTest\Mock;

use Magento\Framework\Mail\EmailMessage;

class TransportBuilderMock extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var EmailMessage
     */
    private $sentMessage;

    protected function reset()
    {
        $this->sentMessage = $this->message;
        parent::reset();
        return $this;
    }

    public function getSentMessage(): EmailMessage
    {
        return $this->sentMessage;
    }

    public function getTransport()
    {
        $this->prepareMessage();
        $this->reset();
        return new TransportMock($this->message);
    }
}
