<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

class ClientFactory
{
    public function __construct(private readonly SftpConnectionFactory $sftpConnectionFactory)
    {
    }

    public function create(LocationConfig $locationConfig): Client
    {
        return new Client($locationConfig, $this->sftpConnectionFactory);
    }
}
