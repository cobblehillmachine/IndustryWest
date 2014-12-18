<?php

class Nick_Ordercorrection_Block_Adminhtml_Sales_Order_Creditmemo_View extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_View
{
	 public function __construct() {
     	
		parent::__construct();
		
		$order = $this->getCreditmemo()->getId();

		if(Mage::helper('ordercorrection/data')->checkRole()) {	
			
			if (Mage::getStoreConfig('ordercorrection/general/enable_creditmemo')) {
			
				$message = Mage::helper('sales')->__('Are you sure you want to delete ALL credit memos for this order?');
				$this->_addButton('delete', array(
					'label'     => Mage::helper('sales')->__('Delete'),
					'class'     => 'delete',
					'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getDeletecreditUrl() . '\')',
				));
			}
		}
	}
	
	public function getDeletecreditUrl(){
        
		return $this->getUrl('ordercorrection/delete/deletecredit/action', array('oid'=>Mage::registry('current_creditmemo')->getOrderId()));
    }


}