<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('shipping_productmatrix')} ADD   algorithm varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('shipping_productmatrix')} ADD   notes varchar(255) NULL;
	
");

$installer->endSetup();


