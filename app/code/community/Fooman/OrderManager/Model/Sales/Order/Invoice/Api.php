<?php

class Fooman_OrderManager_Model_Sales_Order_Invoice_Api extends Mage_Sales_Model_Order_Invoice_Api_V2
{

    public function sendEmail($invoiceIncrementId)
    {
        $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoiceIncrementId);

        if (!$invoice->getId()) {
            $this->_fault('not_exists');
        }

        $invoice->setEmailSent(true);

        try {
            $invoice->sendEmail(true);
            $invoice->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return true;
    }

}