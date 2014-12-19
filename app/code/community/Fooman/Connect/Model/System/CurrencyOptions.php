<?php

class Fooman_Connect_Model_System_CurrencyOptions extends Fooman_Connect_Model_XeroOauth {

    public function toOptionArray() {
        $returnArray = array();
        $returnArray[]=array('value' =>'base','label'=>Mage::helper('foomanconnect')->__('Store Base Currency'));
        $returnArray[]=array('value' =>'order','label'=>Mage::helper('foomanconnect')->__('Order Currency'));
        return $returnArray;
    }

}
