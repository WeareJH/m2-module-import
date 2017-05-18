<?php

use Magento\Framework\Code\Generator\Io;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\ObjectManager\Code\Generator\Factory;
use Magento\Framework\TestFramework\Unit\Autoloader\ExtensionGeneratorAutoloader;

require_once __DIR__ . '/functions.php';

$generatorIo = new Io(new File(), sys_get_temp_dir() . '/var/generation');

spl_autoload_register(function ($className) use ($generatorIo) {
    if (class_exists($className)) {
        return false;
    }

    if (substr($className, -strlen('Factory'), strlen('Factory')) !== 'Factory') {
        //we are only generating factories here
        return false;
    }

    $entitySuffix = ucfirst(Factory::ENTITY_TYPE);
    $sourceClassName = rtrim(substr($className, 0, -1 * strlen($entitySuffix)), '\\');

    if (!class_exists($sourceClassName) && !interface_exists($sourceClassName)) {
        return false;
    }

    include_once (new Factory($sourceClassName, $className, $generatorIo))->generate();
    return true;
});

spl_autoload_register([new ExtensionGeneratorAutoloader($generatorIo), 'load']);
