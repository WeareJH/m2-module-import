<?php

declare(strict_types=1);

namespace Jh\ImportTest\Source;

use Jh\Import\Config;
use Jh\Import\Import\Record;
use Jh\Import\Source\Iterator;
use Jh\Import\Source\SourceConsumer;
use PHPUnit\Framework\TestCase;

class SourceConsumerTest extends TestCase
{
    public function testSourceConsumer(): void
    {
        $source = Iterator::fromCallable(
            function () {
                yield ['sku' => 'PROD1', 'stock' => 10];
                yield ['sku' => 'PROD2', 'stock' => 5];
                yield ['sku' => 'PROD3', 'stock' => 11];
            });

        $consumer = new SourceConsumer();
        $data = $consumer->toArray($source, new Config('my-stock-import', ['id_field' => 'sku']));

        self::assertInstanceOf(Record::class, $data[0]);
        self::assertInstanceOf(Record::class, $data[1]);
        self::assertInstanceOf(Record::class, $data[2]);

        self::assertEquals(['sku' => 'PROD1', 'stock' => 10], $data[0]->asArray());
        self::assertEquals(['sku' => 'PROD2', 'stock' => 5], $data[1]->asArray());
        self::assertEquals(['sku' => 'PROD3', 'stock' => 11], $data[2]->asArray());
    }
}
