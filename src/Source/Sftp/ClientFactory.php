<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

class ClientFactory
{
    public function create(LocationConfig $locationConfig): Client
    {
        return new Client($locationConfig);
    }
}
