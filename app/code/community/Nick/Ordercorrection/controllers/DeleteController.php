<?php

class Nick_Ordercorrection_DeleteController extends Mage_Adminhtml_Controller_Action
{	

	protected function _initOrder()
    {
        $id = $this->
		getRequest()->getParam('oid');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }
	
	protected function _initInvoice()
    {
        $id = $this->getRequest()->getParam('iid');
		
        $invoice = Mage::getModel('sales/order_invoice')->load($id);

        if (!$invoice->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order_invoice', $invoice);
        Mage::register('current_invoice', $invoice);
        return $invoice;
    }
	
	/*Get Order Collection Details*/
	
	public function deleteCollection($collection)
    {
        if(!empty($collection)) {
            foreach($collection as $item) {

                // Try to delete all the comments (sales/order_***_comment)
                $comments = null;
                try {
                    $comments = $item->getCommentsCollection();
                } catch(Exception $e) {};
                if(!empty($comments)) $this->deleteCollection($comments);

                // Try to delete all the items (sales/order_***_item)
                $items = null;
                try {
                    $items = $item->getItemsCollection();
                } catch(Exception $e) {};
                if(!empty($items)) $this->deleteCollection($items);

                // Try to delete the item itself
                try {
                    if(is_object($item)) $item->delete();
                } catch(Exception $e) {};
            }

            return true;
        }
        return false;
    }
    
	public function getCollection($invoice, $name)
    {
        try {
            $collection = Mage::getResourceModel($name)
                ->addAttributeToSelect('*')
                ->addFieldToFilter('entity_id', $invoice->getId());
            return $collection;
        } catch(Exception $e) {
            return array();
        };
    }
	
    public function getCollections($order, $name)
    {
        try {
            $collection = Mage::getResourceModel($name)
                ->addAttributeToSelect('*')
                ->setOrderFilter($order->getId());
			return $collection;
        } catch(Exception $e) {
            return array();
        };
    }
	
	
	
	public function DeleteinvoiceAction()
    {
        if ($invoice = $this->_initInvoice()) {
            try {
            	
                $collection = $this->getCollection($invoice, 'sales/order_invoice_collection');
	
    			$online = 0;
				$offline = 0;
				
				$order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('oid'));
				$invoiceTotal = number_format($invoice->getBaseGrandTotal(), 2);
				$currency = $invoice->getOrderCurrencyCode();
                $orderBaseTax = $order->getBaseTaxInvoiced() - $invoice->getBaseTaxAmount();
				$orderBaseTotal = $order->getBaseTotalInvoiced() - $invoiceTotal;
				$orderShipping = $order->getBaseShippingInvoiced() - $invoice->getBaseShippingAmount();
				$orderSubtotal = $order->getBaseSubtotalInvoiced() - $invoice->getBaseSubtotal();
				$orderShippingTax = $order->getBaseShippingTaxInvoiced() - $invoice->getShippingTaxAmount();
				$orderDiscount = $order->getBaseDiscountInvoiced() - $invoice->getBaseDiscountAmount();			
				
					if($order->getBaseTotalOnlineInvoiced() >= 0) $online = $order->getBaseTotalOnlineInvoiced() - $invoiceTotal;
					if($order->getBaseTotalOfflineInvoiced() >= 0) $online = $order->getBaseTotalOfflineInvoiced() - $invoiceTotal;
				
				/*Update the invoice amounts on the order*/
				 $due = $order->getTotalDue() + $invoice->getBaseGrandTotal();
				
				$order
                ->setBaseDiscountInvoiced($orderDiscount)
                ->setBaseShippingInvoiced($orderShipping)
                ->setBaseSubtotalInvoiced($orderSubtotal)
                ->setBaseTaxInvoiced($orderBaseTax)
                ->setBaseShippingTaxInvoiced($orderShippingTax)
                ->setBaseTotalOnlineInvoiced($online)
                ->setBaseTotalOfflineInvoiced($offline)
                ->setBaseTotalInvoiced($orderBaseTotal)
                ->setTotalOnlineInvoiced($online)
                ->setTotalOfflineInvoiced($offline)
                ->setDiscountInvoiced($orderDiscount)
                ->setShippingInvoiced($orderShipping)
                ->setShippingTaxInvoiced($orderBaseTax)
                ->setSubtotalInvoiced($orderSubtotal)
                ->setTaxInvoiced($orderBaseTax)
                ->setTotalInvoiced($orderBaseTotal)
				->setTotalPaid($orderBaseTotal)
				->setBaseTotalPaid($orderBaseTotal)
				->setBaseTotalDue($due)
				->setTotalDue($due);
        
		/*Get items on the invoice and order */
				
		foreach($invoice->getItemsCollection() as $item)    { 
                  
					foreach($order->getItemsCollection() as $orderitem)    { 
                    	if ($item->getSku() == $orderitem->getSku()) {
							
							$qty = $item->getQty();
							
							$orderitem->setQtyInvoiced($orderitem->getQtyInvoiced() - $qty)
							->setTaxInvoiced($orderitem->getTaxInvoiced() - ($item->getTaxAmount() * $qty))
							->setBaseTaxInvoiced($orderitem->getBaseTaxInvoiced() - ($item->getTaxAmount() * $qty))
							->setRowInvoiced($orderitem->getRowInvoiced() - $item->getRowTotalInclTax())
							->setBaseRowInvoiced($orderitem->getBaseRowInvoiced() - $item->getBaseRowTotalInclTax())
							->setDiscountInvoiced($orderitem->getDiscountInvoiced() - ($item->getDiscountAmount() * $qty))
							->setBaseDiscountInvoiced($orderitem->getBaseDiscountInvoiced() - ($item->getBaseDiscountAmount() * $qty))
							->setHiddenTaxInvoiced($orderitem->getHiddenTaxInvoiced() - ($item->getHiddenTaxAmount() * $qty))
							->setBaseHiddenTaxInvoiced($orderitem->getBaseHiddenTaxInvoiced() - ($item->getBaseHiddenTaxAmount() * $qty))
							->save();
						}
					}
				}	
		/*Delete this invoice*/
		$this->deleteCollection($collection);
		
		/*if other invoices exist lesve it at processing*/
		if ($orderBaseTotal > 1) {
			$status = Mage_Sales_Model_Order::STATE_PROCESSING;
			$order->setData('state', Mage_Sales_Model_Order::STATE_PROCESSING);
			}
		/*else change it back to pending payment*/
		else {
			$status = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
			$order->setData('state', Mage_Sales_Model_Order::STATE_NEW);
			}      
        $order->setStatus($status);
        if (Mage::getStoreConfig('ordercorrection/general/enable_comments') == 1){
			$order->addStatusToHistory($status, 'Invoice Totalling '.$invoiceTotal.$currency.' Has Been Deleted By User '.Mage::getSingleton('admin/session')->getUser()->getUsername(), false);
			}
		 $order->save();
            
                $this->_getSession()->addSuccess(
                    $this->__('Invoice was successfully deleted')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Invoice Could Not Be Deleted'));
            }
             $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }

    }
	
	public function DeleteinvoicesAction()
    {
        if ($order = $this->_initOrder()) {
            try {
            	
                 $collection = $this->getCollections($order, 'sales/order_invoice_collection');
                 $this->deleteCollection($collection);
                
                foreach($order->getItemsCollection() as $item)    { 
                    
                    if ($item->getQtyInvoiced() > 0) {
						$item->setQtyInvoiced(0)
						->setTaxInvoiced(0)
						->setBaseTaxInvoiced(0)
						->setRowInvoiced(0)
						->setBaseRowInvoiced(0)
						->setDiscountInvoiced(0)
						->setBaseDiscountInvoiced(0)
						->setBaseDiscountInvoiced(0)
						->setHiddenTaxInvoiced(0)
						->setBaseHiddenTaxInvoiced(0)			
						->save();
					}
                }
    			
				$amount = number_format($order->getTotalPaid(), 2);
                $due = $order->getGrandTotal();
				
				$order
                ->setBaseDiscountInvoiced(0)
                ->setBaseShippingInvoiced(0)
                ->setBaseSubtotalInvoiced(0)
                ->setBaseTaxInvoiced(0)
                ->setBaseShippingTaxInvoiced(0)
                ->setBaseTotalOnlineInvoiced(0)
                ->setBaseTotalOfflineInvoiced(0)
                ->setBaseTotalInvoiced(0)
                ->setTotalOnlineInvoiced(0)
                ->setTotalOfflineInvoiced(0)
                ->setDiscountInvoiced(0)
                ->setShippingInvoiced(0)
                ->setShippingTaxInvoiced(0)
                ->setSubtotalInvoiced(0)
                ->setTaxInvoiced(0)
                ->setTotalInvoiced(0)
				->setTotalPaid(0)
				->setBaseTotalPaid(0)
				->setBaseTotalDue($due)
				->setTotalDue($due)		
				;
        
        $status = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$order->setData('state', Mage_Sales_Model_Order::STATE_NEW);
        $order->setStatus($status);
		if (Mage::getStoreConfig('ordercorrection/general/enable_comments') == 1){
        	$order->addStatusToHistory($status, 'Invoice(s) Totalling '.$amount.' Have Been Deleted By User '.Mage::getSingleton('admin/session')->getUser()->getUsername(), false);
		}
        $order->save();
            
                $this->_getSession()->addSuccess(
                    $this->__('Invoice was successfully deleted')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Invoice Could Not Be Deleted'));
            }
             $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }

    }
	
	public function DeleteshipmentAction()
    {
        if ($shipment = $this->_initShipment()) {
            try {
            	
                $collection = $this->getCollection($invoice, 'sales/order_shipment_collection');
				
				$order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('oid'));
		
				$incrementId = $shipment->getIncrementId();
				$file = 'Shipment_PDF/'.$incrementId.'.pdf';
				try {
					unlink($file);
					} catch (Exception $e) {
				
				}
				
        
		/*Get items on the invoice and order */
				
		foreach($shipment->getItemsCollection() as $item)    { 
                  
					foreach($order->getItemsCollection() as $orderitem)    { 
                    	if ($item->getSku() == $orderitem->getSku()) {
							
							$qty = $item->getQty();
							
							$orderitem->setQtyShipped($orderitem->getQtyShipped() - $qty)
							->save();
						}
					}
				}	
				
		$this->deleteCollection($collection);
		

			$status = Mage_Sales_Model_Order::STATE_PROCESSING;
			$order->setData('state', Mage_Sales_Model_Order::STATE_PROCESSING);

		
		     
        $order->setStatus($status);
		if (Mage::getStoreConfig('ordercorrection/general/enable_comments') == 1){
        	$order->addStatusToHistory($status, 'Shipment Has Been Deleted By User '.Mage::getSingleton('admin/session')->getUser()->getUsername(), false);
		}
         $order->save();
            
                $this->_getSession()->addSuccess(
                    $this->__('Shipment was successfully deleted')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Shipment Could Not Be Deleted'));
            }
             $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }

    }
	
	
	public function DeleteshipmentsAction()
    {
        if ($order = $this->_initOrder()) {
            try {
            	
                 $collection = $this->getCollections($order, 'sales/order_shipment_collection');
                 $this->deleteCollection($collection);
                
				
				

                foreach($order->getItemsCollection() as $item)    { 
                    
                    if ($item->getQtyInvoiced() > 0) {
						$item->setQtyShipped(0)		
						->save();
					}
                }
    			
        
        $status = Mage_Sales_Model_Order::STATE_PROCESSING;
		$order->setData('state', Mage_Sales_Model_Order::STATE_PROCESSING);
        $order->setStatus($status);
		if (Mage::getStoreConfig('ordercorrection/general/enable_comments') == 1){
              $order->addStatusToHistory($status, 'Shipment(s) For This Order Have Been Deleted By User '.Mage::getSingleton('admin/session')->getUser()->getUsername(), false);
		}
		 $order->save();
            
                $this->_getSession()->addSuccess(
                    $this->__('Shipments were successfully deleted')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Shipments Could Not Be Deleted'));
            }
             $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }

    }
	
	public function ResetAction()
    {
        if ($order = $this->_initOrder()) {
            try {
            	
                 $collection = $this->getCollections($order, 'sales/order_shipment_collection');
                 $this->deleteCollection($collection);
                
				 $collection = $this->getCollections($order, 'sales/order_creditmemo_collection');
                 $this->deleteCollection($collection);
				
				$collection = $this->getCollections($order, 'sales/order_invoice_collection');
                 $this->deleteCollection($collection);

                foreach($order->getItemsCollection() as $item)    { 
                    
                    if ($item->getQtyInvoiced() > 0) {
						$item->setQtyShipped(0)
						->setQtyRefunded(0)
						->setHiddenTaxRefunded(0)
						->setBaseHiddenTaxRefunded(0)
						->setTaxRefunded(0)	
						->setBaseAmountRefunded(0)
						->setAmountRefunded(0)
						->setQtyInvoiced(0)
						->setTaxInvoiced(0)
						->setBaseTaxInvoiced(0)
						->setRowInvoiced(0)
						->setBaseRowInvoiced(0)
						->setDiscountInvoiced(0)
						->setBaseDiscountInvoiced(0)
						->setBaseDiscountInvoiced(0)
						->setHiddenTaxInvoiced(0)
						->setBaseHiddenTaxInvoiced(0)
						->save();
					}
                }
			$due = $order->getGrandTotal();	
			$order
                ->setBaseDiscountRefunded(0)
                ->setBaseShippingRefunded(0)
                ->setBaseSubtotalRefunded(0)
                ->setBaseTaxRefunded(0)
                ->setBaseShippingTaxRefunded(0)
                ->setBaseTotalOnlineRefunded(0)
                ->setBaseTotalOfflineRefunded(0)
                ->setBaseTotalRefunded(0)
                ->setTotalOnlineRefunded(0)
                ->setTotalOfflineRefunded(0)
                ->setDiscountRefunded(0)
                ->setShippingRefunded(0)
                ->setShippingTaxRefunded(0)
                ->setSubtotalRefunded(0)
                ->setTaxRefunded(0)
                ->setTotalRefunded(0)
				->setBaseDiscountInvoiced(0)
                ->setBaseShippingInvoiced(0)
                ->setBaseSubtotalInvoiced(0)
                ->setBaseTaxInvoiced(0)
                ->setBaseShippingTaxInvoiced(0)
                ->setBaseTotalOnlineInvoiced(0)
                ->setBaseTotalOfflineInvoiced(0)
                ->setBaseTotalInvoiced(0)
                ->setTotalOnlineInvoiced(0)
                ->setTotalOfflineInvoiced(0)
                ->setDiscountInvoiced(0)
                ->setShippingInvoiced(0)
                ->setShippingTaxInvoiced(0)
                ->setSubtotalInvoiced(0)
                ->setTaxInvoiced(0)
                ->setTotalInvoiced(0)
				->setTotalPaid(0)
				->setBaseTotalPaid(0)
				->setBaseTotalDue($due)
				->setTotalDue($due);
    			
        
        $status = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$order->setData('state', Mage_Sales_Model_Order::STATE_NEW);
        $order->setStatus($status);
		if (Mage::getStoreConfig('ordercorrection/general/enable_comments') == 1){
              $order->addStatusToHistory($status, 'Order Was Reset By User '.Mage::getSingleton('admin/session')->getUser()->getUsername(), false);
		}
		 $order->save();
            
                $this->_getSession()->addSuccess(
                    $this->__('Order was successfully reset')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Unable to reset this order'));
            }
             $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }

    }
	
	public function DeletecreditAction()
    {
        if ($order = $this->_initOrder()) {
            try {
            	
                 $collection = $this->getCollections($order, 'sales/order_creditmemo_collection');
                 $this->deleteCollection($collection);
                
                foreach($order->getItemsCollection() as $item)    { 
                    
                    if ($item->getQtyRefunded() > 0) {
						$item
						->setQtyRefunded(0)
						->setHiddenTaxRefunded(0)
						->setBaseHiddenTaxRefunded(0)
						->setTaxRefunded(0)	
						->setBaseAmountRefunded(0)
						->setAmountRefunded(0)
						->save();   
					}
                }
    			$amount = number_format($order->getTotalRefunded(),2);
                $order
                ->setBaseDiscountRefunded(0)
                ->setBaseShippingRefunded(0)
                ->setBaseSubtotalRefunded(0)
                ->setBaseTaxRefunded(0)
                ->setBaseShippingTaxRefunded(0)
                ->setBaseTotalOnlineRefunded(0)
                ->setBaseTotalOfflineRefunded(0)
                ->setBaseTotalRefunded(0)
                ->setTotalOnlineRefunded(0)
                ->setTotalOfflineRefunded(0)
                ->setDiscountRefunded(0)
                ->setShippingRefunded(0)
                ->setShippingTaxRefunded(0)
                ->setSubtotalRefunded(0)
                ->setTaxRefunded(0)
                ->setTotalRefunded(0);
        
        $status = Mage_Sales_Model_Order::STATE_COMPLETE;
		$order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
        $order->setStatus($status);
        if (Mage::getStoreConfig('ordercorrection/general/enable_comments') == 1){
			$order->addStatusToHistory($status, 'Credit Memo(s) Totalling '.$amount.' Have Been Deleted By User '.Mage::getSingleton('admin/session')->getUser()->getUsername(), false);
		}
		$order->save();
            
                $this->_getSession()->addSuccess(
                    $this->__('Credit Memo was successfully deleted')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Credit Memo Could Not Be Deleted'));
            }
             $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }

    }
	
	
	public function DeleteorderAction()
    {
        if ($order = $this->_initOrder()) {
            try {
            	
                 $collection = $this->getCollections($order, 'sales/order_shipment_collection');
                 $this->deleteCollection($collection);
                
				 $collection = $this->getCollections($order, 'sales/order_creditmemo_collection');
                 $this->deleteCollection($collection);
				
				 $collection = $this->getCollections($order, 'sales/order_invoice_collection');
                 $this->deleteCollection($collection);
				 
				 $collection = $this->getCollection($order, 'sales/order_collection');
                 $this->deleteCollection($collection);
            
                 $this->_getSession()->addSuccess(
                 $this->__('Order Was Successfully Deleted - You will need to refresh lifetime statistics to get accurate report data')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Order Could Not Be Deleted'));
            }
             $this->_redirect('adminhtml/sales_order');
        }

    }
}