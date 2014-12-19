<?php
class Fooman_PdfCustomiser_Model_System_Columns
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'name', 'label' => Mage::helper('sales')->__('Product')),
            array('value' => 'sku', 'label' => Mage::helper('sales')->__('SKU')),
            array('value' => 'name-sku', 'label' => Mage::helper('sales')->__('Product').' + ' .Mage::helper('sales')->__('SKU')),
            array('value' => 'barcode', 'label' => Mage::helper('pdfcustomiser')->__('SKU Barcode')),
            array('value' => 'image', 'label' => Mage::helper('pdfcustomiser')->__('Product Image')),
            array('value' => 'custom', 'label' => Mage::helper('pdfcustomiser')->__('Custom Column')),
            array('value' => 'price', 'label' => Mage::helper('sales')->__('Price')),
            array('value' => 'discount', 'label' => Mage::helper('sales')->__('Discount')),
            array('value' => 'qty', 'label' => Mage::helper('sales')->__('Qty')),
            array('value' => 'tax', 'label' => Mage::helper('sales')->__('Tax')),
            array('value' => 'subtotal', 'label' => Mage::helper('sales')->__('Subtotal'))
        );
    }
}
