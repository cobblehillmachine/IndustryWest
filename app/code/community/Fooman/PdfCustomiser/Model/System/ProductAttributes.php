<?php
class Fooman_PdfCustomiser_Model_System_ProductAttributes
{
    public function toOptionArray()
    {
        $options = array();
        $collection = Mage::getResourceModel('catalog/product_attribute_collection');
        foreach ($collection as $attribute){
            $options[] = array(
                'value'=>$attribute->getAttributeCode(),
                'label'=>($attribute->getFrontendLabel()?$attribute->getFrontendLabel():$attribute->getAttributeCode())
            );
        }
        return $options;
    }
}
