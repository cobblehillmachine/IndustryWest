<?php

class Nick_Ordercorrection_Block_Adminhtml_Sales_Order_Invoice_View extends Mage_Adminhtml_Block_Sales_Order_Invoice_View
{
	 public function __construct() {
     	
		parent::__construct();
		
		$order = $this->getInvoice()->getId();
			
		if(Mage::helper('ordercorrection/data')->checkRole()) {	
			
			if (Mage::getStoreConfig('ordercorrection/general/enable_invoice')) {
				
				$message = Mage::helper('sales')->__('Are you sure you want to delete ALL invoices for this order?');
				$this->_addButton('deleteall', array(
					'label'     => Mage::helper('sales')->__('Delete ALL Invoices'),
					'class'     => 'delete',
					'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getDeleteInvoicesUrl() . '\')',
					)
				);
					
				
				$message = Mage::helper('sales')->__('Are you sure you want to delete THIS invoice for this order?');
				$this->_addButton('delete', array(
					'label'     => Mage::helper('sales')->__('Delete Invoice'),
					'class'     => 'delete',
					'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getDeleteInvoiceUrl() . '\')',
					)
				);
			}
		}
	}
	
	public function getDeleteInvoicesUrl(){
        
		return $this->getUrl('ordercorrection/delete/deleteinvoices/action', array('oid'=>Mage::registry('current_invoice')->getOrderId()));
    }
	
	public function getDeleteInvoiceUrl(){
        
		return $this->getUrl('ordercorrection/delete/deleteinvoice/action', array('oid'=>Mage::registry('current_invoice')->getOrderId(), 'iid'=>Mage::registry('current_invoice')->getId()));
    }


}