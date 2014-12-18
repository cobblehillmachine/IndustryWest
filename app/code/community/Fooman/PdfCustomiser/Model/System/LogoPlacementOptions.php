<?php
class Fooman_PdfCustomiser_Model_System_LogoPlacementOptions
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'auto', 'label' => Mage::helper('pdfcustomiser')->__('automatic')),
            array('value' => 'auto-right', 'label' => Mage::helper('pdfcustomiser')->__('automatic') . ' 2'),
            array('value' => 'no-scaling', 'label' => Mage::helper('pdfcustomiser')->__('no scaling')),
            array('value' => 'no-scaling-right', 'label' => Mage::helper('pdfcustomiser')->__('no scaling') . ' 2'),
            array('value' => 'manual', 'label' => Mage::helper('pdfcustomiser')->__('manual'))
        );
    }


}