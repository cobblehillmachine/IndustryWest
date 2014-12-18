<?php
class Fooman_PdfCustomiser_Model_System_ShipmentDisplay
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'none', 'label'=>Mage::helper('pdfcustomiser')->__('None')),
            array('value'=>'image', 'label'=>Mage::helper('pdfcustomiser')->__('Product Image')),
            //array('value'=>'barcode', 'label'=>Mage::helper('pdfcustomiser')->__('SKU Barcode'))
        );
    }


}