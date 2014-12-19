<?php
class Fooman_PdfCustomiser_Model_System_Pagesizes
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'A4', 'label'=>Mage::helper('pdfcustomiser')->__('A4')),
            array('value'=>'LETTER', 'label'=>Mage::helper('pdfcustomiser')->__('letter'))
        );
    }

}