<?php

$installer = $this;

$installer->startSetup();
if(version_compare(Mage::getVersion(),'1.4.1.0') >= 0){
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_creditmemo'), 'xero_creditnote_id', "varchar(255)");
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_creditmemo'), 'xero_creditnote_number', "varchar(255)");
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_creditmemo'), 'xero_export_status', "smallint(5) default 0");
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_creditmemo'), 'xero_last_validation_errors', "text");
} else {
    $installer->addAttribute('creditmemo', 'xero_last_validation_errors', array('type' => 'text','label' => 'Xero Last Validation Errors','is_required'=>0));
    $installer->addAttribute('creditmemo', 'xero_creditnote_id', array('type' => 'varchar','label' => 'Xero Invoice Id','is_required'=>0));
    $installer->addAttribute('creditmemo', 'xero_creditnote_number', array('type' => 'varchar','label' => 'Xero Invoice Number','is_required'=>0));
    $installer->addAttribute('creditmemo', 'xero_export_status', array('type' => 'int','label' => 'Xero Export Status','is_required'=>0,'default' => '0'));
}
$installer->endSetup();



