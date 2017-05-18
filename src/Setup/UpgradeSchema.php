<?php

namespace Jh\Import\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $table = $setup->getConnection()
                ->newTable('import_lock')
                ->addColumn('import_name', Table::TYPE_TEXT);

            $setup->getConnection()->createTable($table);
            
            $table = $setup->getConnection()
                ->newTable('jh_import_history')
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary'  => true
                    ]
                )
                ->addColumn(
                    'source_id',
                    Table::TYPE_TEXT,
                    64
                )
                ->addColumn(
                    'started',
                    Table::TYPE_DATETIME
                )
                ->addColumn(
                    'finished',
                    Table::TYPE_DATETIME
                );

            $setup->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $setup->getConnection()->addColumn(
                'jh_import_history',
                'import_name',
                [
                    'type'    => Table::TYPE_TEXT,
                    'length'  => 64,
                    'comment' => 'import_name'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $importLogTable = $setup->getConnection()
                ->newTable('jh_import_history_log')
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary'  => true
                    ]
                )
                ->addColumn(
                    'history_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ]
                )
                ->addForeignKey(
                    $setup->getFkName('jh_import_history_log', 'history_id', 'jh_import_history', 'id'),
                    'history_id',
                    'jh_import_history',
                    'id'
                )
                ->addColumn(
                    'message',
                    Table::TYPE_TEXT,
                    512,
                    [
                        'nullable' => false
                    ]
                );

            $setup->getConnection()->createTable($importLogTable);

            $importLogItemTable = $setup->getConnection()
                ->newTable('jh_import_history_item_log')
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary'  => true
                    ]
                )
                ->addColumn(
                    'history_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ]
                )
                ->addForeignKey(
                    $setup->getFkName('jh_import_history_item_log', 'history_id', 'jh_import_history', 'id'),
                    'history_id',
                    'jh_import_history',
                    'id'
                )
                ->addColumn(
                    'reference_line',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => true,
                    ]
                )
                ->addColumn(
                    'id_field',
                    Table::TYPE_TEXT,
                    64,
                    [
                        'unsigned' => true,
                        'nullable' => true,
                    ]
                )
                ->addColumn(
                    'id_value',
                    Table::TYPE_TEXT,
                    64,
                    [
                        'unsigned' => true,
                        'nullable' => true,
                    ]
                )
                ->addColumn(
                    'created',
                    Table::TYPE_DATETIME,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'error_level',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'message',
                    Table::TYPE_TEXT,
                    512,
                    [
                        'nullable' => false
                    ]
                );

            $setup->getConnection()->createTable($importLogItemTable);
        }

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $setup->getConnection()->addColumn(
                'jh_import_history_log',
                'log_level',
                [
                    'type'    => Table::TYPE_TEXT,
                    'length'  => 64,
                    'comment' => 'log_level'
                ]
            );

            $setup->getConnection()->addColumn(
                'jh_import_history_item_log',
                'log_level',
                [
                    'type'    => Table::TYPE_TEXT,
                    'length'  => 64,
                    'comment' => 'log_level'
                ]
            );

            $setup->getConnection()->dropColumn('jh_import_history_item_log', 'error_level');
        }

        if (version_compare($context->getVersion(), '1.5.0', '<')) {
            $setup->getConnection()->addColumn(
                'jh_import_history_log',
                'created',
                [
                    'type'     => Table::TYPE_DATETIME,
                    'nullable' => false,
                    'comment'  => 'created'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.6.0', '<')) {
            $setup->getConnection()->renameTable('import_lock', 'jh_import_lock');
        }

        if (version_compare($context->getVersion(), '1.7.0', '<')) {
            $setup->getConnection()->addColumn(
                'jh_import_history',
                'memory_usage',
                [
                    'type'    => Table::TYPE_TEXT,
                    'length'  => 64,
                    'comment' => 'memory_usage'
                ]
            );
        }

        $setup->endSetup();
    }
}
