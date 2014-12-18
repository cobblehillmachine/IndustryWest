<?php

class Nick_Ordercorrection_RestoreController extends Mage_Adminhtml_Controller_Action
{	

	protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/restoreorder')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Sales'), Mage::helper('adminhtml')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Restore Order'), Mage::helper('adminhtml')->__('Restore Order'))
        ;
        return $this;
    }
	
	public function indexAction()
    {
         $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('ordercorrection/restore'))
            ->renderLayout();
    }
	
	public function restorePostAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                
				$incrementId = $this->getRequest()->getPost('restore_number');
				$quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter('reserved_order_id',$incrementId)->getFirstItem();
				
				if($quote->getEntityId()){
					$quote->collectTotals()->save();
					$service = Mage::getModel('sales/service_quote', $quote);
					
					$service->submitAll();
					$newId = Mage::getModel("sales/order")->getCollection()->getLastItem()->getIncrementId();
					Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ordercorrection')->__('Order was successfully retrieved for number - '. $incrementId.' - A new order has been created - '.$newId));
				} else {
					
					Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ordercorrection')->__('Order - '. $incrementId.' Was Not Found In the Database. Please check the order number and try again.'));
				
				}
				
            }
            catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        else {
            Mage::getSingleton('adminhtml/session')->addError('Failed To Restore Order');
        }
        $this->_redirect('*/*');
    }

}