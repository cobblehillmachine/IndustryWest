<?php
class Fooman_OrderManager_Model_System_OrderStatusOptions {

    public function toOptionArray() {
        $returnArray=array();
        $returnArray[]=array('value' =>'','label'=>  Mage::helper('ordermanager')->__('Default'));
        foreach (Mage::getModel('sales/order_config')->getStatuses() as $status=>$statusLabel) {
            $returnArray[]=array('value' =>$status,'label'=>  $statusLabel);
        }
        return $returnArray;
    }

}
