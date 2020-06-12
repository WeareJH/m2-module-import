<?php

namespace Jh\Import\AttributeProcessor;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Jh\Brands\Model\BrandRepository;
use Jh\Brands\Model\Brand as BrandModel;
use Jh\Brands\Model\BrandFactory;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Brand implements IndividualAttributeProcessor
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $file;

    /**
     * @var BrandFactory
     */
    private $brandFactory;

    /**
     * @var BrandRepository
     */
    private $brandRepository;

    /**
     * @var bool
     */
    private $initialised = false;

    /**
     * @var BrandModel[]
     */
    private $brands = [];

    public function __construct(
        BrandFactory $brandFactory,
        BrandRepository $brandRepository,
        File $file,
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->brandFactory = $brandFactory;
        $this->brandRepository = $brandRepository;
    }

    public function process(AttributeInterface $attribute, string $value, Record $record, ReportItem $reportItem): int
    {
        $this->initialise();

        if (isset($this->brands[$value])) {
            return $this->brands[$value]->getId();
        }

        $data = [
            'name'        => $value,
            'description' => $value,
        ];

        $reportItem->addDebug(sprintf('Attempting to import brand with name: "%s"', $value));
        if ($record->columnExists('brand_image') && !empty($record->getColumnValue('brand_image'))) {
            try {
                $data['logo'] = $this->importImage($record->getColumnValue('brand_image'));
            } catch (\InvalidArgumentException $e) {
                $reportItem->addWarning(
                    sprintf('Could not import image for brand: "%s". Error: %s', $value, $e->getMessage())
                );
            }
        }

        $brand = $this->brandFactory
            ->create()
            ->setData($data);

        try {
            $this->brandRepository->save($brand);
            $reportItem->addDebug(sprintf('Created new brand: "%s". ID: "%s"', $value, $brand->getId()));
            $this->brands[$value] = $brand; //cache for later usages
        } catch (CouldNotSaveException $e) {
            $reportItem->addWarning(sprintf('Brand: "%s" could not be saved. Error: %s', $value, $e->getMessage()));
            throw new CouldNotCreateOptionException();
        }

        return $brand->getId();
    }

    private function initialise()
    {
        if (false === $this->initialised) {
            $this->brands = collect($this->brandRepository->getList(new SearchCriteria())->getItems())
                ->keyBy(function (BrandModel $brand) {
                    return $brand->getName();
                })
                ->all();

            $this->initialised = true;
        }
    }

    private function importImage(string $imagePath): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        if (!$this->file->isExists($imagePath)) {
            throw new \InvalidArgumentException(sprintf('Image: "%s" does not exist', $imagePath));
        }

        $imageName   = basename($imagePath);
        $destination = $mediaDirectory->getAbsolutePath(BrandModel::MEDIA_PATH) . '/' . $imageName;

        if ($this->file->isExists($destination)) {
            throw new \InvalidArgumentException(
                sprintf('Image with the name: "%s" already exists in the pub folder', $imageName)
            );
        }

        try {
            $this->file->createDirectory(dirname($destination));
            $this->file->rename($imagePath, $destination);
        } catch (FileSystemException $e) {
            throw new \InvalidArgumentException(sprintf('Image could not be renamed: "%s"', $e->getMessage()));
        }

        return BrandModel::MEDIA_PATH . '/' . $imageName;
    }
}
