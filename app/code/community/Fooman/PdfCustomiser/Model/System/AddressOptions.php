<?php
class Fooman_PdfCustomiser_Model_System_AddressOptions
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'billing', 'label'=>Mage::helper('pdfcustomiser')->__('Billing Address only')),
            array('value'=>'shipping', 'label'=>Mage::helper('pdfcustomiser')->__('Shipping Address only')),
            array('value'=>'both', 'label'=>Mage::helper('pdfcustomiser')->__('Both Addresses'))
        );
    }


}