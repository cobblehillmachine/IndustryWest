<?php

class Fooman_Connect_Block_Adminhtml_Tax_Rate_Form extends Mage_Adminhtml_Block_Tax_Rate_Form {

    protected function _prepareForm() {
        parent::_prepareForm();

        $rateId = (int)$this->getRequest()->getParam('rate');
        $rateObject = new Varien_Object();
        $rateModel  = Mage::getSingleton('tax/calculation_rate');
        $rateObject->setData($rateModel->getData());

        $fieldset = $this->getForm()->addFieldset('foomanconnect_fieldset', array('legend'=>Mage::helper('foomanconnect')->__('Fooman Connect')));
        $fieldset->addField('xero_rate', 'select',
                array(
                'name' => "xero_rate",
                'label' => Mage::helper('foomanconnect')->__('Xero Rate'),
                'title' => Mage::helper('foomanconnect')->__('Xero Rate'),
                'value' => $rateObject->getXeroRate(),
                'values' => Mage::helper('foomanconnect')->getTaxOptions(),
                'required' => true,
                'class' => 'required-entry'
                )
        );
        return $this;
    }

}