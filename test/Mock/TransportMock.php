<?php

declare(strict_types=1);

namespace Jh\ImportTest\Mock;

use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\TransportInterface;

class TransportMock implements TransportInterface
{
    /**
     * @var EmailMessageInterface|null
     */
    private $message;

    public function __construct(EmailMessageInterface $message = null)
    {
        $this->message = $message;
    }

    public function sendMessage(): void
    {
        //noop
    }

    public function getMessage(): ?EmailMessageInterface
    {
        return $this->message;
    }
}
