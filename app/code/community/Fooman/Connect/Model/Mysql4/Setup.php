<?php

class Fooman_Connect_Model_Mysql4_Setup extends Mage_Sales_Model_Mysql4_Setup {
    protected $resourcePreviousState = NULL;

    public function startSetup() {
        $this->resourcePreviousState = Mage::registry('resource');
        Mage::unregister('resource');
        Mage::register('resource', true);

        return parent::startSetup();
    }

    public function endSetup() {
        Mage::unregister('resource');
        if($this->resourcePreviousState !== null) {
            Mage::register('resource', $this->resourcePreviousState);
        }

        return parent::endSetup();
    }


}