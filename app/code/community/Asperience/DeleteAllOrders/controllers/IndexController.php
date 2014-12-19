<?php
/**
 * @category   ASPerience
 * @package    Asperience_DeleteAllOrders
 * @author     ASPerience - www.asperience.fr
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once 'Mage/Adminhtml/controllers/Sales/OrderController.php';

class Asperience_DeleteAllOrders_IndexController extends Mage_Adminhtml_Sales_OrderController
{
    protected function _construct()
    {
        $this->setUsedModuleName('Asperience_DeleteAllOrders');
    }
    /**
     * Delete selected orders
     */
    public function indexAction()
    {
        // Get orders to delete in grid
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        
        // Test if deletion active
        if (Mage::getStoreConfig(Asperience_DeleteAllOrders_Model_Order::XML_PATH_SALES_IS_ACTIVE)) {
            $nbDelOrder = $nbDelInvoice = $nbDelShipment = $nbDelCreditmemo = 0;
            $nbDelOrderGrid = $nbDelInvoiceGrid = $nbDelShipmentGrid = $nbDelCreditmemoGrid = 0;
            $nbDelTax = $nbDelQuote = 0;
            $ordersDelete = $invoicesDelete = $creditsDelete = $shipmentsDelete = array();
            $ordersGridDelete = $invoicesGridDelete = $creditsGridDelete = $shipmentsGridDelete = array();
            $taxesDelete = array();
            $quotesDelete = array();
            $ordersUndelete = array();
            //Database connection for additional code when lack of integrity constraints
            $conn = Mage::getSingleton('core/resource')->getConnection('asperience/deleteallorders');
            try {
                foreach ($orderIds as $orderId) {
                    //Load order
                    $order = Mage::getModel('deleteallorders/order')->load($orderId);
                    if ($order->getIncrementId()) {
                        $orderLoaded = True;
                    } else {
                        $this->_getSession()->addWarning($this->__('No order loaded for id %s', $orderId));
                        $orderLoaded = False;
                    }
                    $quoteId = False;
                    //Regular delete
                    $orderToDelete = False;
                    if ($orderLoaded) {
                        //Check delete conditions
                        //  order without invoices/...
                        //     or order with invoices/... but state that permits it
                        if ($order->canDelete()) {
                            $orderToDelete = True;
                            // Delete linked invoices
                            if ($order->hasInvoices()) {
                                $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                                    ->setOrderFilter($orderId)->load();
                                foreach ($invoices as $invoice) {
                                    $invoice = Mage::getModel('sales/order_invoice')->load($invoice->getId());
                                    $invoicesDelete[] = $invoice->getIncrementId();
                                    $invoice->delete();
                                    $nbDelInvoice++;
                                }
                            }
                            // Delete linked shipments
                            if ($order->hasShipments()) {
                                $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                                    ->setOrderFilter($orderId)->load();
                                foreach ($shipments as $shipment) {
                                    $shipment = Mage::getModel('sales/order_shipment')->load($shipment->getId());
                                    $shipmentsDelete[] = $shipment->getIncrementId();
                                    $shipment->delete();
                                    $nbDelShipment++;
                                }
                            }
                            // Delete linked credit memos
                            if ($order->hasCreditmemos()) {
                                $creditmemos = Mage::getResourceModel('sales/order_creditmemo_collection')
                                    ->setOrderFilter($orderId)->load();
                                foreach ($creditmemos as $creditmemo) {
                                    $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemo->getId());
                                    $creditsDelete[] = $creditmemo->getIncrementId();
                                    $creditmemo->delete();
                                    $nbDelCreditmemo++;
                                }
                            }
                            // Delete order
                            $order = Mage::getModel('sales/order')->load($orderId);
                            $ordersDelete[] = $order->getIncrementId();
                            $quoteId = $order->getQuoteId();
                            $order->delete();
                            $nbDelOrder++;
                        } else {
                            //Order is not deletable
                            $ordersUndelete[] = $order->getIncrementId();
                        }
                    }
                    
                    //Additionnal code if lack of integrity constraints
                    if ($orderToDelete || !$orderLoaded) {
                        // Delete grid linked invoices if not deleted by constraints
                        $invoices = Mage::getResourceModel('sales/order_invoice_grid_collection')
                            ->addFieldToFilter('order_id', $orderId);
                        foreach ($invoices as $invoice) {
                            $invoicesGridDelete[] = $invoice->getIncrementId();
                            $conn->delete(Mage::getSingleton('core/resource')->getTableName('sales/invoice_grid'),
                                    array('increment_id = ?' => (int) $invoice->getIncrementId()));
                            $nbDelInvoiceGrid++;
                        }

                        // Delete grid linked shipments if not deleted by constraints
                        $shipments = Mage::getResourceModel('sales/order_shipment_grid_collection')
                            ->addFieldToFilter('order_id', $orderId);
                        foreach ($shipments as $shipment) {
                            $shipmentsGridDelete[] = $shipment->getIncrementId();
                            $conn->delete(Mage::getSingleton('core/resource')->getTableName('sales/shipment_grid'),
                                    array('increment_id = ?' => (int) $shipment->getIncrementId()));
                            $nbDelShipmentGrid++;
                        }
                        
                        // Delete grid linked credit memos if not deleted by constraints
                        $creditMemos = Mage::getResourceModel('sales/order_creditmemo_grid_collection')
                            ->addFieldToFilter('order_id', $orderId);
                        foreach ($creditMemos as $creditMemo) {
                            $creditsGridDelete[] = $creditMemo->getIncrementId();
                            $conn->delete(Mage::getSingleton('core/resource')->getTableName('sales/creditmemo_grid'),
                                    array('increment_id = ?' => (int) $creditMemo->getIncrementId()));
                            $nbDelCreditmemoGrid++;
                        }

                        // Delete grid linked orders if not deleted by constraints
                        $orders = Mage::getResourceModel('sales/order_grid_collection')
                            ->addFieldToFilter('entity_id', $orderId);
                        foreach ($orders as $order) {
                            $ordersGridDelete[] = $order->getIncrementId();
                            $conn->delete(Mage::getSingleton('core/resource')->getTableName('sales/order_grid'),
                                    array('entity_id = ?' => (int) $order->getEntityId()));
                            $nbDelOrderGrid++;
                        }

                        /* Tables that should be deleted by constraints
                            downloadable_link_purchased
                            downloadable_link_purchased_item
                            sales_billing_agreement_order
                            sales_flat_order_address
                            sales_flat_order_payment
                            sales_flat_order_status_history
                            sales_flat_shipment_comment
                            sales_flat_order_item
                            sales_order_tax_item
                            sales_payment_transaction
                            sales_recurring_profile_order
                        */
                        
                        // Tables that are not deleted by constraints
                        //    sales_billing_agreement
                    }
                    
                    //Additionnal code for tables without integrity constraints in regular version
                    if ($orderToDelete) {
                        // Delete taxes
                        $taxes = Mage::getModel('tax/sales_order_tax')->getCollection()
                            ->addFieldToFilter('order_id', $orderId);
                        foreach ($taxes as $tax) {
                            $taxesDelete[] = $tax->getTaxId();
                            Mage::getModel('tax/sales_order_tax')->load($tax->getId())->delete();
                            $nbDelTax++;
                        }
                        
                        // Delete quote
                        $quotesDelete[] = $quoteId;
                        Mage::getModel('sales/quote')->load($quoteId)->delete();
                        $nbDelQuote++;
                    }
                }
            } catch (Exception $e){
                $this->_getSession()->addError(
                    $this->__('An error arose during the deletion. %s', $e));
            }
            // User messages format
            if ($nbDelOrder > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s order(s) was/were successfully deleted.',
                        $nbDelOrder));
                $this->_getSession()->addSuccess(implode(" ", $ordersDelete));
            }
            if ($nbDelOrderGrid > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s order(s) was/were successfully deleted in grid.',
                        $nbDelOrderGrid));
                $this->_getSession()->addSuccess(implode(" ", $ordersGridDelete));
            }
            if ($nbDelInvoice > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s invoice(s) was/were successfully deleted.',
                        $nbDelInvoice));
                $this->_getSession()->addSuccess(implode(" ", $invoicesDelete));
            }
            if ($nbDelInvoiceGrid > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s invoice(s) was/were successfully deleted in grid.',
                        $nbDelInvoiceGrid));
                $this->_getSession()->addSuccess(implode(" ", $invoicesGridDelete));
            }
            if ($nbDelShipment > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s shipment(s) was/were successfully deleted.',
                        $nbDelShipment));
                $this->_getSession()->addSuccess(implode(" ", $shipmentsDelete));
            }
            if ($nbDelShipmentGrid > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s shipment(s) was/were successfully deleted in grid.',
                        $nbDelShipmentGrid));
                $this->_getSession()->addSuccess(implode(" ", $shipmentsGridDelete));
            }
            if ($nbDelCreditmemo > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s credit memo(s) was/were successfully deleted.',
                        $nbDelCreditmemo));
                $this->_getSession()->addSuccess(implode(" ", $creditsDelete));
            }
            if ($nbDelCreditmemoGrid > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s credit memo(s) was/were successfully deleted in grid.',
                        $nbDelCreditmemoGrid));
                $this->_getSession()->addSuccess(implode(" ", $creditsGridDelete));
            }
            if ($nbDelTax > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s order tax(es) was/were successfully deleted.',
                        $nbDelTax));
            }
            if ($nbDelQuote > 0) {
                $this->_getSession()->addSuccess(
                    $this->__('%s quote(s) was/were successfully deleted.',
                        $nbDelQuote));
            }
            if (count($ordersUndelete) > 0) {
                $this->_getSession()->addWarning(
                    $this->__('Selected order(s) can not be deleted due to configuration:'));
                $this->_getSession()->addWarning(implode(" ", $ordersUndelete));
            }
        } else {
            // Deletion is deactivated
            $this->_getSession()->addError($this->__('This feature was deactivated.'));
        }
        $this->_redirect('adminhtml/sales_order/', array());
    }
}
