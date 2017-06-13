<?php

namespace Jh\ImportTest\Controller\Adminhtml\Files;

use Jh\Import\Config\Data;
use Jh\Import\Controller\Adminhtml\Files\Download;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Config\CacheInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\DriverPool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class DownloadTest extends TestCase
{
    use ObjectHelper;

    /**
     * @var FileFactory|ObjectProphecy
     */
    private $fileFactory;

    /**
     * @var string
     */
    private $tempDirectory;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var WriteFactory
     */
    private $writeFactory;

    /**
     * @var Index
     */
    private $controller;

    public function setUp()
    {
        $this->tempDirectory = sprintf('%s/%s/var', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->tempDirectory, 0777, true);

        $this->directoryList = new DirectoryList(dirname($this->tempDirectory));
        $this->writeFactory = new WriteFactory(new DriverPool());

        $context = $this->getObject(Context::class);

        $this->fileFactory = $this->prophesize(FileFactory::class);

        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files', 'incoming' => 'jh_import/incoming'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $data = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $this->controller = new Download(
            $context,
            $this->fileFactory->reveal(),
            $this->directoryList,
            $this->writeFactory,
            $data
        );
    }

    public function tearDown()
    {
        (new Filesystem)->remove($this->tempDirectory);
    }

    public function testRedirectIsReturnedIfNameParamNotPreset()
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn(false);

        $redirect = $this->prophesize(Redirect::class);
        $redirect->setPath('*/*/index')->willReturn($redirect);

        $this->retrieveChildMock(Context::class, 'resultRedirectFactory')
            ->create()
            ->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->controller->execute());
    }

    public function testRedirectIsReturnedIfImportDoesNotExist()
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn('image');

        $redirect = $this->prophesize(Redirect::class);
        $redirect->setPath('*/*/index')->willReturn($redirect);

        $this->retrieveChildMock(Context::class, 'resultRedirectFactory')
            ->create()
            ->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->controller->execute());
    }

    public function testFileResponseIsReturned()
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn('product');

        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('directory')
            ->willReturn('incoming');

        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('file')
            ->willReturn('my-file.csv');

        @mkdir($this->tempDirectory . '/jh_import/incoming', 0777, true);
        file_put_contents($this->tempDirectory . '/jh_import/incoming/my-file.csv', '1,2,3,4');

        $response = $this->prophesize(ResultInterface::class);

        $this->fileFactory
            ->create('my-file.csv', '1,2,3,4')
            ->willReturn($response->reveal());

        self::assertSame($response->reveal(), $this->controller->execute());
    }
}
