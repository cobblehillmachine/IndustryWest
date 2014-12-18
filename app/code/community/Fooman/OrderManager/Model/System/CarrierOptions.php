<?php
class Fooman_OrderManager_Model_System_CarrierOptions {

    public function toOptionArray() {

        $returnArray = array();
        $shipConfig = Mage::getSingleton('shipping/config');
        $returnArray[]=array('value' =>'custom','label'=> Mage::helper('ordermanager')->__('Custom Carrier') );
        foreach ($shipConfig->getAllCarriers() as $code => $carrier) {
            $returnArray[]=array('value' =>$code,'label'=> $this->_getCarrierTitle($code) );
        }
        return $returnArray;
    }

    private function _getCarrierTitle($code)
    {
        $title = Mage::helper('ordermanager')->getCarrierTitle($code);
        return (empty($title))?$code:$title;
    }

}
