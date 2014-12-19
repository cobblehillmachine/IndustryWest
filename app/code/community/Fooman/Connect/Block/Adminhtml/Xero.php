<?php

class Fooman_Connect_Block_Adminhtml_Xero extends Mage_Adminhtml_Block_Widget_Grid_Container {

    private function getSession() {
        $_session = Mage::getSingleton('adminhtml/session');
        return $_session;
    }

    public function __construct() {

        $this->_addButton('process_xero', array(
                'label'     => Mage::helper('foomanconnect')->__('Process All Unexported Orders'),
                'onclick'   => "location.href='".$this->getUrl('*/*/processAll')."'",
                'class'     => '',
        ));

        if(Mage::helper('foomanconnect')->getMageStoreConfig('xeroenablereset')) {
            $this->_addButton('reset_xero', array(
                    'label'     => Mage::helper('foomanconnect')->__('Reset All'),
                    'onclick'   => "location.href='".$this->getUrl('*/*/resetAll')."'",
                    'class'     => '',
            ));
        }

        $this->_controller = 'adminhtml_xero';
        $this->_blockGroup = 'foomanconnect';
        $this->_headerText = Mage::helper('foomanconnect')->__('Fooman Connect: Xero - Orders');

        parent::__construct();
        $this->_removeButton('add');
    }

}