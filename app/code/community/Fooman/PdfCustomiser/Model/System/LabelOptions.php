<?php
class Fooman_PdfCustomiser_Model_System_LabelOptions
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'0', 'label'=>Mage::helper('core')->__('No')),
            array('value'=>'singleshipping', 'label'=>Mage::helper('pdfcustomiser')->__('Single - Shipping Address Label')),
            array('value'=>'singlebilling', 'label'=>Mage::helper('pdfcustomiser')->__('Single - Billing Address Label')),
            array('value'=>'double', 'label'=>Mage::helper('pdfcustomiser')->__('Double - Both Addresses')),
            array('value'=>'doublereturn', 'label'=>Mage::helper('pdfcustomiser')->__('Double - Shipping and Return Addresses'))
        );
    }


}