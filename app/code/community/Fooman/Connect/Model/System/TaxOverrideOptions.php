<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Model_System_TaxOverrideOptions extends Fooman_Connect_Model_XeroOauth {

    const MAGE_CALC = 'mage';
    const XERO_CALC = 'xero';

    public function toOptionArray() {
        $returnArray = array();
        $returnArray[]=array('value' =>self::MAGE_CALC,'label'=>Mage::helper('foomanconnect')->__('Magento calculated'));
        $returnArray[]=array('value' =>self::XERO_CALC,'label'=>Mage::helper('foomanconnect')->__('Pass only totals to Xero'));
        return $returnArray;
    }

}
