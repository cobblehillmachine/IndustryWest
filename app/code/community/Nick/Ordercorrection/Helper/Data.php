<?php

class Nick_Ordercorrection_Helper_Data extends Mage_Core_Helper_Abstract{
	
	public function addDeleteInvoicebutton($order) {
		
		$order = Mage::getModel('sales/order')->load(Mage::registry('sales_order')->getId());
		
		$disabled = true;
		
		if ($this->checkInvoices($order) && $this->checkRole()) $disabled = false;
		
		$message = Mage::helper('sales')->__('Are you sure you want to delete ALL invoices for this order?');		
		
		$url = Mage::helper('adminhtml')->getUrl('ordercorrection/delete/deleteinvoices/action', array('oid'=>Mage::registry('sales_order')->getId()));
		
		return array(
			'label'	 => $this->__('Delete Invoices'),
			'disabled'  => $disabled,
			'class'	=> 'scaleable delete',
			'onclick'   => 'deleteConfirm(\''.$message.'\', \'' .$url. '\')',
		);		
	}
	
	public function addDeleteCreditMemobutton() {
		
		$order = Mage::getModel('sales/order')->load(Mage::registry('sales_order')->getId());
		
		$disabled = true;
		
		if ($this->checkCreditmemos($order) && $this->checkRole()) $disabled = false;
			
		$message = Mage::helper('sales')->__('Are you sure you want to delete ALL credit memos for this order?');
		$url = Mage::helper('adminhtml')->getUrl('ordercorrection/delete/deletecredit/action', array('oid'=>Mage::registry('sales_order')->getId()));
					
		return array(
			'label'	 => $this->__('Delete Credit Memos'),
			'disabled'  => $disabled,
			'class'	=> 'scaleable delete',
			'onclick'   => 'deleteConfirm(\''.$message.'\', \'' .$url. '\')',				
		);
		
	}
	
	
	public function addDeleteShipmentbutton() {
		
		$order = Mage::getModel('sales/order')->load(Mage::registry('sales_order')->getId());
		
		$disabled = true;
		
		if ($this->checkShipments($order) && $this->checkRole()) $disabled = false;
		
		$message = Mage::helper('sales')->__('Are you sure you want to delete ALL shipments for this order?');
		$url = Mage::helper('adminhtml')->getUrl('ordercorrection/delete/deleteshipments/action', array('oid'=>Mage::registry('sales_order')->getId()));
					
		return array(
			'label'	 => $this->__('Delete Shipments'),
			'disabled'  => $disabled,
			'class'	=> 'scaleable delete',
			'onclick'   => 'deleteConfirm(\''.$message.'\', \'' .$url. '\')',
		);	
	}
	
	public function addResetbutton() {
		
		$order = Mage::getModel('sales/order')->load(Mage::registry('sales_order')->getId());
		
		$message = Mage::helper('sales')->__('Are you sure you want to reset this order?');
		$url = Mage::helper('adminhtml')->getUrl('ordercorrection/delete/reset/action', array('oid'=>Mage::registry('sales_order')->getId()));
		
		$disabled = true;
		
		if($this->checkRole()) $disabled = false;
		
		return array(
			'label'	 => $this->__('Reset'),
			'class'	=> 'scaleable fail',
			'disabled'  => $disabled,
			'onclick'   => 'deleteConfirm(\''.$message.'\', \'' .$url. '\')',
		);	
	}
	
	
	public function addDeletebutton() {
		
		$order = Mage::getModel('sales/order')->load(Mage::registry('sales_order')->getId());
		
		$message = Mage::helper('sales')->__('Are you sure you want to DELETE THIS ENTIRE ORDER?');
		$url = Mage::helper('adminhtml')->getUrl('ordercorrection/delete/deleteorder/action', array('oid'=>Mage::registry('sales_order')->getId()));
		
		$disabled = true;
		
		if($this->checkRole()) $disabled = false;
		
		return array(
			'label'	 => $this->__('Delete'),
			'class'	=> 'scaleable fail',
			'disabled'  => $disabled,
			'onclick'   => 'deleteConfirm(\''.$message.'\', \'' .$url. '\')',
		);	
	}
	
	
	public function checkInvoices($order) {
	
		$invoice = Mage::getResourceModel('sales/order_invoice_collection')
							->addAttributeToSelect('*')
							->setOrderFilter($order->getId())
							->getFirstItem();
		if($invoice->getIncrementId() != NULL) return true;
		else return false;
	
	}
	
	public function checkShipments($order) {
		
		$shipment = Mage::getResourceModel('sales/order_shipment_collection')
							->addAttributeToSelect('*')
							->setOrderFilter($order->getId())
							->getFirstItem();
		if($shipment->getIncrementId() != NULL) return true;
		else return false;
	
	}
	
	public function checkCreditmemos($order) {
	
		$creditmemo = Mage::getResourceModel('sales/order_creditmemo_collection')
							->addAttributeToSelect('*')
							->setOrderFilter($order->getId())
							->getFirstItem();
		if($creditmemo->getIncrementId() != NULL) return true;
		else return false;
	
	}
	
	public function checkRole() {
	
		$settings = Mage::getStoreConfig('ordercorrection');
		$enabled = $settings['general']['enable_roles'];
		
		if(!$enabled) return true;
		
		$roleSelection = $settings['general']['roles'];
		
		$roleId = implode('', Mage::getSingleton('admin/session')->getUser()->getRoles());

		$roles = explode(',',$roleSelection);
				
			foreach ($roles as $role)  if ($role == $roleId) return true;
				
		return false;		
				
	}
}