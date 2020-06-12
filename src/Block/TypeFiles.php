<?php

namespace Jh\Import\Block;

use Illuminate\Support\Collection;
use Jh\Import\Config;
use Jh\Import\Type\FileMatcher;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class TypeFiles extends Template
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var WriteFactory
     */
    private $writeFactory;

    /**
     * @var FileMatcher
     */
    private $fileMatcher;

    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        WriteFactory $writeFactory,
        FileMatcher $fileMatcher
    ) {
        parent::__construct($context);

        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
        $this->fileMatcher = $fileMatcher;

        $this->setTemplate('Jh_Import::info_type_files.phtml');
    }

    public function getImport(): Config
    {
        return $this->getParentBlock()->getImport();
    }

    public function filesNew(): array
    {
        return $this->getFilesInDir('incoming_directory')->all();
    }

    public function filesFailed(): array
    {
        return $this->getFilesInDir('failed_directory')
            ->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                 return $b->getCTime() <=> $a->getCTime();
            })
            ->take(15)
            ->all();
    }

    public function filesArchived(): array
    {
        return $this->getFilesInDir('archived_directory')
            ->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                return $b->getCTime() <=> $a->getCTime();
            })
            ->take(15)
            ->all();
    }

    public function fileWillBeProcessed(\SplFileInfo $file): bool
    {
        return $this->fileMatcher->matches($this->getImport()->get('match_files'), $file->getFilename());
    }

    private function getFilesInDir(string $dir): Collection
    {
        $directoryWriter = $this->writeFactory->create(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $this->getImport()->get($dir)
            )
        );

        //ensure directory is created
        $directoryWriter->create();

        return collect($directoryWriter->read())
            ->map(function (string $file) use ($directoryWriter) {
                return $directoryWriter->getAbsolutePath($file);
            })
            ->map(function ($file) {
                return new \SplFileInfo($file);
            });
    }

    public function incomingDirectory()
    {
        return sprintf(
            '%s/%s',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            $this->getImport()->get('incoming_directory')
        );
    }

    public function archivedDirectory(): string
    {
        return sprintf(
            '%s/%s',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            $this->getImport()->get('archived_directory')
        );
    }

    public function failedDirectory(): string
    {
        return sprintf(
            '%s/%s',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            $this->getImport()->get('failed_directory')
        );
    }

    public function getChangeTime(\SplFileInfo $file): string
    {
        return (new \DateTime('@' . $file->getCTime()))->format('M jS, Y H:i:s');
    }

    public function getDownloadUrl(\SplFileInfo $file, string $directory): string
    {
        return $this->getUrl(
            'jh_import/files/download',
            [
                'name'      => $this->getImport()->getImportName(),
                'directory' => urlencode($directory),
                'file'      => urlencode($file->getFilename())
            ]
        );
    }

    public function getDeleteUrl(\SplFileInfo $file, string $directory): string
    {
        return $this->getUrl(
            'jh_import/files/delete',
            [
                'name'      => $this->getImport()->getImportName(),
                'directory' => urlencode($directory),
                'file'      => urlencode($file->getFilename())
            ]
        );
    }
}
