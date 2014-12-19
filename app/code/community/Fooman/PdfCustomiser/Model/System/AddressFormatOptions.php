<?php
class Fooman_PdfCustomiser_Model_System_AddressFormatOptions
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'pdf', 'label'=>Mage::helper('pdfcustomiser')->__('Default')),
            array('value'=>'us', 'label'=>Mage::helper('pdfcustomiser')->__('US')),
            array('value'=>'european', 'label'=>Mage::helper('pdfcustomiser')->__('European')),
            array('value'=>'jp', 'label'=>Mage::helper('pdfcustomiser')->__('Japan'))
        );
    }


}