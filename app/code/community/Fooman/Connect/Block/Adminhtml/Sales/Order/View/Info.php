<?php

class Fooman_Connect_Block_Adminhtml_Sales_Order_View_Info extends Mage_Adminhtml_Block_Sales_Order_View_Info {

    protected function _prepareLayout() {
        $this->getLayout()
                ->getBlock('content')->append(
                $this->getLayout()->createBlock('foomanconnect/adminhtml_sales_order_xero')
        );
        return parent::_prepareLayout();
    }


}
