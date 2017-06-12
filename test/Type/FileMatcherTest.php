<?php

namespace Jh\ImportTest\Type;

use Jh\Import\Type\FileMatcher;
use PHPUnit\Framework\TestCase;

class FileMatcherTest extends TestCase
{
    public function testFilesImportWithSingleFileMatch()
    {
        $files = [
            '/var/www/import/file1.csv',
            '/var/www/import/file2.csv',
            '/var/www/import/file3.csv',
            '/var/www/import/other.txt'
        ];

        $matcher = new FileMatcher;
        $matched = $matcher->matched('other.txt', $files)->all();

        self::assertCount(1, $matched);
        self::assertEquals(['/var/www/import/other.txt'], $matched);
    }

    public function testFilesImportWithRegexFolderMatch()
    {
        $files = [
            '/var/www/import/file1.csv',
            '/var/www/import/file2.csv',
            '/var/www/import/file3.csv',
            '/var/www/import/other.txt'
        ];

        $matcher = new FileMatcher;
        $matched = $matcher->matched('/file\d.csv/', $files)->all();

        self::assertCount(3, $matched);
        self::assertEquals(
            [
                '/var/www/import/file1.csv',
                '/var/www/import/file2.csv',
                '/var/www/import/file3.csv',
            ],
            $matched
        );
    }

    public function testFilesImportWithAllFilesMatch()
    {
        $files = [
            '/var/www/import/file1.csv',
            '/var/www/import/file2.csv',
            '/var/www/import/file3.csv',
            '/var/www/import/other.txt'
        ];

        $matcher = new FileMatcher;
        $matched = $matcher->matched('*', $files)->all();

        self::assertCount(4, $matched);
        self::assertEquals(
            [
                '/var/www/import/file1.csv',
                '/var/www/import/file2.csv',
                '/var/www/import/file3.csv',
                '/var/www/import/other.txt'
            ],
            $matched
        );
    }

    public function testMatchesWithSingleFile()
    {
        $matcher = new FileMatcher;

        self::assertTrue($matcher->matches('*', '/var/www/import/file1.csv'));
        self::assertTrue($matcher->matches('/file\d\.csv/', '/var/www/import/file1.csv'));
        self::assertTrue($matcher->matches('file1.csv', '/var/www/import/file1.csv'));

        self::assertFalse($matcher->matches('/file\d\.csv/', '/var/www/import/something.csv'));
        self::assertFalse($matcher->matches('file1.csv', '/var/www/import/file2.csv'));
    }
}
