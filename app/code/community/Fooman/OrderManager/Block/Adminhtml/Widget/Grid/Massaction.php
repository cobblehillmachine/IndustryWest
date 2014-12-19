<?php

class Fooman_OrderManager_Block_Adminhtml_Widget_Grid_Massaction extends Mage_Adminhtml_Block_Widget_Grid_Massaction
{
    public function getJavaScript()
    {
	if($this->getRequest()->getControllerName() =='sales_order'||
                    $this->getRequest()->getControllerName() =='adminhtml_sales_order') {
        	return str_replace('varienGridMassaction','foomanGridMassaction',parent::getJavaScript());
	} else {
		return parent::getJavaScript();
	}
    }

}
