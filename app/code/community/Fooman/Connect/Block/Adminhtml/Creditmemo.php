<?php

class Fooman_Connect_Block_Adminhtml_Creditmemo extends Mage_Adminhtml_Block_Widget_Grid_Container {

    private function getSession() {
        $_session = Mage::getSingleton('adminhtml/session');
        return $_session;
    }

    public function __construct() {

        $this->_addButton('process_xero', array(
                'label'     => Mage::helper('foomanconnect')->__('Process All Unexported Credit Memos'),
                'onclick'   => "location.href='".$this->getUrl('*/*/processAll')."'",
                'class'     => '',
        ));


        $this->_controller = 'adminhtml_creditmemo';
        $this->_blockGroup = 'foomanconnect';
        $this->_headerText = Mage::helper('foomanconnect')->__('Fooman Connect: Xero - Credit Memos');

        parent::__construct();
        $this->_removeButton('add');
    }

}