<?php
class Fooman_Connect_Model_System_OrderStatusOptions extends Fooman_Connect_Model_XeroOauth {

    public function toOptionArray() {
        $returnArray=array();
        foreach (Mage::getModel('sales/order_config')->getStatuses() as $status=>$statusLabel) {
            $returnArray[]=array('value' =>$status,'label'=>  $statusLabel);
        }
        return $returnArray;
    }

}
