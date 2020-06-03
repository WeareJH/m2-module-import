<?php

declare(strict_types=1);

namespace Jh\ImportTest\Mock;

use Magento\Framework\Mail\EmailMessageInterface;

class TransportMock implements \Magento\Framework\Mail\TransportInterface
{
    /**
     * @var EmailMessageInterface|null
     */
    private $message;

    public function __construct(EmailMessageInterface $message = null)
    {
        $this->message = $message;
    }

    public function sendMessage()
    {
        //noop
    }

    public function getMessage()
    {
        return $this->message;
    }
}
