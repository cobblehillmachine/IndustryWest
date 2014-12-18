<?php
/*
 * @author     Kristof Ringleff
 * @package    Fooman_OrderManager
 * @copyright  Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 * @copyright  Copyright (c) 2009 smARTstudiosUK Limited (http://smartebusiness.co.uk)
 */

//require_once BP.'/app/code/core/Mage/Adminhtml/Controller/Action.php';

class Fooman_OrderManager_Sales_OrderManagerController extends Mage_Adminhtml_Controller_Action {

    /*
     * Ship all selected orders
     */

    function shipallAction() {
        $orderApi = Mage::getModel('sales/order_api');
        $shipApi = Mage::getModel('ordermanager/sales_order_shipment_api');
        $successes = array();
        $errors = array();

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);
        $trackingNumbers = $this->_getTrackingNumbers();
        $carrierCodes = $this->_getCarrierCodes();

        $sendEmail = Mage::helper('ordermanager')->getStoreConfig('shipaction/sendemail');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('shipaction/newstatus');

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();

                try {
                    //temporarily suppress the automatic copy to admin
                    $copyTo = Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $order->getStoreId());
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, ''); //this is not saved to db

                    //let's create the shipment
                    $shipmentIncrementId = $shipApi->create(
                                    $orderIncrementId,                                      //order increment id
                                    array(),                                                //items Qty, null = all
                                    Mage::helper('ordermanager')->__('Created Shipment'),   //comment
                                    false,                                                  //email customer
                                    false                                                   //include comment in email
                    );

                    $shipmentTrackId = true;
                    //add tracking info if we have it
                    if($shipmentIncrementId && isset($carrierCodes[$orderId]) && isset ($trackingNumbers[$orderId]) && !empty($trackingNumbers[$orderId])) {
                        $shipmentTrackId = $shipApi->addTrack(
                                        $shipmentIncrementId,                                                           //shipment increment id
                                        $carrierCodes[$orderId],                                                        //carrier code
                                        Mage::helper('ordermanager')->getCarrierTitle($carrierCodes[$orderId]),         //title
                                        $trackingNumbers[$orderId]                                                      //tracking number
                        );                        
                    }
                    //are we updating the status?
                    if($newOrderStatus) {
                        $orderApi->addComment (
                                                $orderIncrementId,
                                                $newOrderStatus,
                                                Mage::helper('ordermanager')->__('Created Shipment'),
                                                false
                                );

                    }
                    
                    //set back to original
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $copyTo);
                    //send email
                    if($sendEmail) {
                        $shipApi->sendEmail($shipmentIncrementId);
                    }

                    if($shipmentIncrementId && $shipmentTrackId) {
                        $successes[] = $orderIncrementId;
                    }else {
                        $errors[] = $orderIncrementId;
                    }
                }
                catch (Mage_Api_Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                }
                catch (Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }
                unset($order);
        }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Shipped').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');

    }


    /*
     * Invoice all selected orders
     */

    function invoiceallAction() {
        $orderApi = Mage::getModel('sales/order_api');
        $invoiceApi = Mage::getModel('ordermanager/sales_order_invoice_api');
        $successes = array();
        $errors = array();

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);

        $sendEmail = Mage::helper('ordermanager')->getStoreConfig('invoiceaction/sendemail');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('invoiceaction/newstatus');

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();

                try {
                    //let's create the invoice
                    $invoiceIncrementId = $invoiceApi->create(
                                    $orderIncrementId,                                      //order increment id
                                    array(),                                                //items Qty, null = all
                                    Mage::helper('ordermanager')->__('Created Invoice'),    //comment
                                    $sendEmail,                                             //email customer
                                    false                                                   //include comment in email
                    );

                    //are we updating the status?
                    if($newOrderStatus) {
                        $orderApi->addComment (
                                                $orderIncrementId,
                                                $newOrderStatus,
                                                Mage::helper('ordermanager')->__('Created Invoice'),
                                                false
                                );

                    }

                    if($invoiceIncrementId) {
                        $successes[] = $orderIncrementId;
                    }else {
                        $errors[] = $orderIncrementId;
                    }
                }
                catch (Mage_Api_Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                }
                catch (Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }
                unset($order);
            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Invoiced').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     *      change status all
     */

    function statusallAction() {

        $successes = array();
        $errors = array();
        $orderApi = Mage::getModel('sales/order_api');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('statusaction/newstatus');

		$newOrderStatus = 'ready_to_ship';

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();

                try {
                    //update the status?
                    $orderApi->addComment (
                                            $orderIncrementId,
                                            $newOrderStatus,
                                            '',
                                            false
                            );
                }
                catch (Mage_Api_Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                }
                catch (Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }

                unset($order);
            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Status changed').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');
    }




    /**
     *      capture all
     */

    function captureallAction() {
        $orderApi = Mage::getModel('sales/order_api');
        $invoiceApi = Mage::getModel('ordermanager/sales_order_invoice_api');
        $successes = array();
        $errors = array();

        $sendEmail = Mage::helper('ordermanager')->getStoreConfig('captureaction/sendemail');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('captureaction/newstatus');

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();
                $nrInvoices = 0;
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $nrInvoices++;
                    if ($invoice->canCapture()) {
                        try {
                            //let's get the money
                            $invoiceIncrementId=$invoice->getIncrementId();
                            $return = $invoiceApi->capture($invoiceIncrementId);

                            //are we updating the status?
                            if($return && $newOrderStatus) {
                                $orderApi->addComment (
                                                        $orderIncrementId,
                                                        $newOrderStatus,
                                                        Mage::helper('ordermanager')->__('Captured Invoice'),
                                                        false
                                        );

                            }

                            if ($sendEmail) {
                                $invoiceApi->addComment (
                                                        $invoiceIncrementId,
                                                        Mage::helper('ordermanager')->__('Captured Invoice'),
                                                        true,
                                                        true
                                        );
                            }

                            if($return) {
                                $successes[] = $orderIncrementId;
                            }
                            else {
                                $errors[] = $orderIncrementId;
                            }
                        }
                        catch (Mage_Api_Exception $e ) {
                            $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                        }
                        catch (Exception $e ) {
                            $errors[] = $orderIncrementId.": ".$e->getMessage();
                        }
                    } else {
                        $errors[] = $orderIncrementId.": ".Mage::helper('ordermanager')->__("can't capture");
                    }
                }
                if (!$nrInvoices) {
                    $errors[] = $orderIncrementId.": ".Mage::helper('ordermanager')->__("can't capture - order has no invoices");
                }
                unset($order);
            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Captured').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');
    }


    /*
     * Invoice and ship all selected orders
     */

    function invoiceandshipallAction() {
        $orderApi = Mage::getModel('sales/order_api');
        $shipApi = Mage::getModel('ordermanager/sales_order_shipment_api');
        $invoiceApi = Mage::getModel('ordermanager/sales_order_invoice_api');
        $successes = array();
        $errors = array();

        $sendInvoiceEmail = Mage::helper('ordermanager')->getStoreConfig('invoiceshipaction/sendinvoiceemail');
        $sendShipEmail = Mage::helper('ordermanager')->getStoreConfig('invoiceshipaction/sendshipemail');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('invoiceshipaction/newstatus');

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);

        $trackingNumbers = $this->_getTrackingNumbers();
        $carrierCodes = $this->_getCarrierCodes();

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();

                try {
                    //temporarily suppress the automatic copy to admin
                    $copyTo = Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, $order->getStoreId());
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, ''); //this is not saved to db

                    //let's create the invoice
                    $invoiceIncrementId = $invoiceApi->create(
                                    $orderIncrementId,                                      //order increment id
                                    array(),                                                //items Qty, null = all
                                    Mage::helper('ordermanager')->__('Created Invoice'),    //comment
                                    false,                                                  //email customer
                                    false                                                   //include comment in email
                    );
                    
                    //set back to original
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, $copyTo);

                    //send email
                    if ($sendInvoiceEmail && $invoiceIncrementId) {
                        $invoiceApi->sendEmail($invoiceIncrementId);
                    }
                }
                catch (Mage_Api_Exception $e ) {
                    $invoiceIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                }
                catch (Exception $e ) {
                    $invoiceIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }

                try {

                    //temporarily suppress the automatic copy to admin
                    $copyTo = Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $order->getStoreId());
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, ''); //this is not saved to db

                    //let's create the shipment
                    $shipmentIncrementId = $shipApi->create(
                                    $orderIncrementId,                                      //order increment id
                                    array(),                                                //items Qty, null = all
                                    Mage::helper('ordermanager')->__('Created Shipment'),   //comment
                                    false,                                                  //email customer
                                    false                                                   //include comment in email
                    );

                    $shipmentTrackId = true;
                    //add tracking info if we have it
                    if($shipmentIncrementId && isset($carrierCodes[$orderId]) && isset ($trackingNumbers[$orderId]) && !empty($trackingNumbers[$orderId])) {
                        $shipmentTrackId = $shipApi->addTrack(
                                        $shipmentIncrementId,                                                           //shipment increment id
                                        $carrierCodes[$orderId],                                                        //carrier code
                                        Mage::helper('ordermanager')->getCarrierTitle($carrierCodes[$orderId]),         //title
                                        $trackingNumbers[$orderId]                                                      //tracking number
                        );                        
                    }

                    //are we updating the status?
                    if($newOrderStatus) {
                        $orderApi->addComment (
                                                $orderIncrementId,
                                                $newOrderStatus,
                                                Mage::helper('ordermanager')->__('Created Invoice and Shipment'),
                                                false
                                );

                    }

                    //set back to original
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $copyTo);

                    //send email
                    if ($sendShipEmail && $shipmentIncrementId) {
                        $shipApi->sendEmail($shipmentIncrementId);
                    }
                }
                catch (Mage_Api_Exception $e ) {
                    $shipmentIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                }
                catch (Exception $e ) {
                    $shipmentIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }

                if($invoiceIncrementId && $shipmentIncrementId) {
                    $successes[] = $orderIncrementId;
                }
                unset($order);
            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Invoiced and shipped').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');
    }

    /*
     * Invoice and capture all selected orders
     */

    function invoiceandcaptureallAction() {
        $orderApi = Mage::getModel('sales/order_api');
        $invoiceApi = Mage::getModel('ordermanager/sales_order_invoice_api');
        $successes = array();
        $errors = array();

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);

        $sendEmail = Mage::helper('ordermanager')->getStoreConfig('invoicecaptureaction/sendemail');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('invoicecaptureaction/newstatus');

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();

                try {
                    //temporarily suppress the automatic copy to admin
                    $copyTo = Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, $order->getStoreId());
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, ''); //this is not saved to db

                    //let's create the invoice
                    $invoiceIncrementId = $invoiceApi->create(
                                    $orderIncrementId,                                      //order increment id
                                    array(),                                                //items Qty, null = all
                                    Mage::helper('ordermanager')->__('Created Invoice'),    //comment
                                    false,                                                  //email customer
                                    false                                                   //include comment in email
                    );

                    //get the money
                    $return = $invoiceApi->capture($invoiceIncrementId);

                    //are we updating the status?
                    if($newOrderStatus) {
                        $orderApi->addComment (
                                                $orderIncrementId,
                                                $newOrderStatus,
                                                Mage::helper('ordermanager')->__('Created Invoice'),
                                                false
                                );

                    }

                    //set back to original
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, $copyTo);

                    //send email
                    if($sendEmail) {
                        $invoiceApi->sendEmail($invoiceIncrementId);
                    }

                    if($invoiceIncrementId) {
                        $successes[] = $orderIncrementId;
                    }else {
                        $errors[] = $orderIncrementId;
                    }
                } catch (Mage_Api_Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                } catch (Exception $e ) {
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }
                unset($order);
            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Invoiced and Captured').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');
    }    
    

    /*
     * capture and ship all selected orders
     */

    function captureandshipallAction() {
        $orderApi = Mage::getModel('sales/order_api');
        $shipApi = Mage::getModel('ordermanager/sales_order_shipment_api');
        $invoiceApi = Mage::getModel('ordermanager/sales_order_invoice_api');
        $successes = array();
        $errors = array();

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);

        $sendEmail = Mage::helper('ordermanager')->getStoreConfig('captureshipaction/sendemail');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('captureshipaction/newstatus');

        $trackingNumbers = $this->_getTrackingNumbers();
        $carrierCodes = $this->_getCarrierCodes();

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();
                $nrInvoices = 0;
                $return = false;
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $nrInvoices++;
                    if ($invoice->canCapture()) {
                        try {
                            //let's get the money
                            $invoiceIncrementId=$invoice->getIncrementId();
                            $return = $invoiceApi->capture($invoiceIncrementId);

                        } catch (Mage_Api_Exception $e ) {
                            $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                        } catch (Exception $e ) {
                            $errors[] = $orderIncrementId.": ".$e->getMessage();
                        }
                    } else {
                        $errors[] = $orderIncrementId.": ".Mage::helper('ordermanager')->__("can't capture, shipment won't be created");
                    }
                }
                if (!$nrInvoices) {
                    $errors[] = $orderIncrementId.": ".Mage::helper('ordermanager')->__("can't capture - order has no invoices, shipment won't be created");
                }
                if ($return) {
                    try {
                        //temporarily suppress the automatic copy to admin
                        $copyTo = Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $order->getStoreId());
                        $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, ''); //this is not saved to db

                        //let's create the shipment
                        $shipmentIncrementId = $shipApi->create(
                                        $orderIncrementId,                                      //order increment id
                                        array(),                                                //items Qty, null = all
                                        Mage::helper('ordermanager')->__('Created Shipment'),   //comment
                                        false,                                                  //email customer
                                        false                                                   //include comment in email
                        );

                        $shipmentTrackId = true;
                        //add tracking info if we have it
                        if($shipmentIncrementId && isset($carrierCodes[$orderId]) && isset ($trackingNumbers[$orderId]) && !empty($trackingNumbers[$orderId])) {
                            $shipmentTrackId = $shipApi->addTrack(
                                            $shipmentIncrementId, //shipment increment id
                                            $carrierCodes[$orderId], //carrier code
                                            Mage::helper('ordermanager')->getCarrierTitle($carrierCodes[$orderId]), //title
                                            $trackingNumbers[$orderId]                                                      //tracking number
                            );
                        }

                        //are we updating the status?
                        if ($newOrderStatus) {
                            $orderApi->addComment(
                                    $orderIncrementId,
                                    $newOrderStatus,
                                    Mage::helper('ordermanager')->__('Created Invoice and Shipment'),
                                    false
                            );
                        }

                        //set back to original
                        $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $copyTo);

                        //send email
                        if ($sendEmail) {
                            $shipApi->sendEmail($shipmentIncrementId);
                        }
                    } catch (Mage_Api_Exception $e) {
                        $errors[] = $orderIncrementId . ": " . $e->getCustomMessage();
                    } catch (Exception $e) {
                        $errors[] = $orderIncrementId . ": " . $e->getMessage();
                    }
                }
                unset($order);
            }
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Captured and shipped').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');
    }

    /*
     * Invoice and ship all selected orders
     */

    function invoicecaptureshipallAction() {
        $orderApi =  Mage::getModel('sales/order_api');
        $shipApi =  Mage::getModel('ordermanager/sales_order_shipment_api');
        $invoiceApi =  Mage::getModel('ordermanager/sales_order_invoice_api');
        $successes = array();
        $errors = array();

        $sendInvoiceEmail = Mage::helper('ordermanager')->getStoreConfig('invoicecaptureshipaction/sendinvoiceemail');
        $sendShipEmail = Mage::helper('ordermanager')->getStoreConfig('invoicecaptureshipaction/sendshipemail');
        $newOrderStatus = Mage::helper('ordermanager')->getStoreConfig('invoicecaptureshipaction/newstatus');

        //Get order_ids from POST
        $orderIds = $this->getRequest()->getPost('order_ids');
        sort($orderIds);

        $trackingNumbers = $this->_getTrackingNumbers();
        $carrierCodes = $this->_getCarrierCodes();

        //loop through orders
        if (is_array($orderIds) && !empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $orderIncrementId = $order->getIncrementId();

                try {
                    //temporarily suppress the automatic copy to admin
                    $copyTo = Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, $order->getStoreId());
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, ''); //this is not saved to db

                    //let's create the invoice
                    $invoiceIncrementId = $invoiceApi->create(
                                    $orderIncrementId,                                      //order increment id
                                    array(),                                                //items Qty, null = all
                                    Mage::helper('ordermanager')->__('Created Invoice'),    //comment
                                    false,                                                  //email customer
                                    false                                                   //include comment in email
                    );

                    //get the money
                    $return = $invoiceApi->capture($invoiceIncrementId);

                    //set back to original
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, $copyTo);

                    //send email
                    if ($sendInvoiceEmail && $invoiceIncrementId) {
                        $invoiceApi->sendEmail($invoiceIncrementId);
                    }
                } catch (Mage_Api_Exception $e ) {
                    $invoiceIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                } catch (Exception $e ) {
                    $invoiceIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }

                try {
                    //temporarily suppress the automatic copy to admin
                    $copyTo = Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $order->getStoreId());
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, ''); //this is not saved to db

                    //let's create the shipment
                    $shipmentIncrementId = $shipApi->create(
                                    $orderIncrementId,                                      //order increment id
                                    array(),                                                //items Qty, null = all
                                    Mage::helper('ordermanager')->__('Created Shipment'),   //comment
                                    false,                                                  //email customer
                                    false                                                   //include comment in email
                    );

                    $shipmentTrackId = true;
                    //add tracking info if we have it
                    if($shipmentIncrementId && isset($carrierCodes[$orderId]) && isset ($trackingNumbers[$orderId]) && !empty($trackingNumbers[$orderId])) {
                        $shipmentTrackId = $shipApi->addTrack(
                                        $shipmentIncrementId,                                                           //shipment increment id
                                        $carrierCodes[$orderId],                                                        //carrier code
                                        Mage::helper('ordermanager')->getCarrierTitle($carrierCodes[$orderId]),         //title
                                        $trackingNumbers[$orderId]                                                      //tracking number
                        );
                    }

                    //are we updating the status?
                    if($newOrderStatus) {
                        $orderApi->addComment (
                                                $orderIncrementId,
                                                $newOrderStatus,
                                                Mage::helper('ordermanager')->__('Created Invoice and Shipment'),
                                                false
                                );

                    }

                    //set back to original
                    $order->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, $copyTo);

                    //send email
                    if ($sendShipEmail && $shipmentIncrementId) {
                        $shipApi->sendEmail($shipmentIncrementId);
                    }
                } catch (Mage_Api_Exception $e ) {
                    $shipmentIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getCustomMessage();
                } catch (Exception $e ) {
                    $shipmentIncrementId=false;
                    $errors[] = $orderIncrementId.": ".$e->getMessage();
                }

                if($invoiceIncrementId && $shipmentIncrementId) {
                    $successes[] = $orderIncrementId;
                }
            }
            unset($order);
        }
        //Add results to session
        if(!empty($errors)) {
            $this->_getSession()->addError(implode("<br/>",$errors));
        }
        if(!empty($successes)) {
            $this->_getSession()->addSuccess(Mage::helper('ordermanager')->__('Invoiced, captured and shipped').': '.implode(",",$successes));
        }
        //go back to the order overview page
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * retrieve tracking numbers from post
     * sort into array by order_id
     */
    private function _getTrackingNumbers ()
    {
        $trackingNumbersSorted = array();
        $trackingNumbersRaw = explode(",", $this->getRequest()->getPost('tracking'));
        foreach ($trackingNumbersRaw as $trackingNumberRaw) {
            list($orderId, $number) = explode("|", $trackingNumberRaw);
            $trackingNumbersSorted[$orderId] = $number;
        }
        return $trackingNumbersSorted;
    }

    /**
     * retrieve carrier codes from post
     * sort into array by order_id
     */
    private function _getCarrierCodes ()
    {
        $carrierCodesSorted = array();
        $carrierCodesRaw = explode(",", $this->getRequest()->getPost('carrier'));
        foreach ($carrierCodesRaw as $carrierCodeRaw) {
            list($orderId, $code) = explode("|", $carrierCodeRaw);
            $carrierCodesSorted[$orderId] = $code;
        }
        return $carrierCodesSorted;
    }
}
