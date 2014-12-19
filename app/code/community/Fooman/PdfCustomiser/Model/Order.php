<?php

class Fooman_PdfCustomiser_Model_Order extends Fooman_PdfCustomiser_Model_Abstract
{
    /**
    * Creates PDF using the tcpdf library from array of orderIds
    * @param array $invoices, $orderIds
    * @access public
    */
    public function getPdf($ordersGiven = array(),$orderIds = array(), $pdf = null, $suppressOutput = false)
    {

        //check if there is anything to print
		if(empty($pdf) && empty($ordersGiven) && empty($orderIds)){
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('There are no printable documents related to selected orders'));
			return false;
		}

        //we will be working through an array of orderIds later - fill it up if only $ordersGiven is available
        if(!empty($ordersGiven)){
            foreach ($ordersGiven as $orderGiven) {
                    $orderIds[] = $orderGiven->getId();
            }
        }

        $this->_beforeGetPdf();

        $storeId = $order = Mage::getModel('sales/order')->load($orderIds[0])->getStoreId();

        //work with a new pdf or add to existing one
        if(empty($pdf)){
            $pdf = Mage::getModel('pdfcustomiser/mypdf', array('P', 'mm',  Mage::getStoreConfig('sales_pdf/all/allpagesize',$storeId), true, 'UTF-8', false));
        }

        foreach ($orderIds as $orderId) {
            //load data
			
            $order = Mage::getModel('sales/order')->load($orderId);

            // create new helper
            $orderHelper = Mage::helper('pdfcustomiser/pdf_order');

            $storeId = $order->getStoreId();
            if ($storeId) {
                $appEmulation = Mage::getSingleton('core/app_emulation');
                $initial = $appEmulation->startEnvironmentEmulation($storeId,Mage_Core_Model_App_Area::AREA_FRONTEND,true);
            }

            $orderHelper->setStoreId($storeId);
            $orderHelper->setSalesObject($order);
            $pdf->setStoreId($storeId);
            $pdf->setPdfHelper($orderHelper);
            // set standard pdf info
            $pdf->SetStandard($orderHelper);

            // add a new page
            $pdf->AddPage();
            $pdf->setIncrementId($order->getIncrementId());
            
            // Print the logo
            $pdf->printHeader($orderHelper, $orderHelper->getPdfTitle());
            
            // Prepare Line Items
            $pdf->prepareLineItems($orderHelper, $order, $order);
            
            // Prepare Top
            $top = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_block')->setPdfHelper($orderHelper)->setTemplate('fooman/pdfcustomiser/order/top.phtml')->toHtml();
            
            $processor = Mage::helper('cms')->getBlockTemplateProcessor();
            $processor->setVariables(
                    array(
                        'order' => $order,
                        'sales_object' => $order,                        
                        'billing_address'=> $pdf->PrepareCustomerAddress($orderHelper, $order, 'billing'),
                        'shipping_address'=> $pdf->PrepareCustomerAddress($orderHelper, $order, 'shipping'),
                        'payment'=> $pdf->PreparePayment ($orderHelper, $order, $order),
                        'shipping'=> $pdf->PrepareShipping ($orderHelper, $order, $order)
                    )
            );
            $top = $processor->filter($top);
            
            //Prepare Totals
            $totals = $this->PrepareTotals($orderHelper, $order);
            
            //Prepare Bottom
            $bottom = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_block')
                    ->setPdf($pdf)
                    ->setPdfHelper($orderHelper)
                    ->setTotals($totals)
                    ->setTemplate('fooman/pdfcustomiser/order/bottom.phtml')
                    ->toHtml();            
            $processor->setVariables(
                    array(
                        'order' => $order,
                        'sales_object' => $order
                    )
            );            
            $bottom = $processor->filter($bottom);

            //Prepare Items
            $items = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_items')->setPdf($pdf)->setPdfHelper($orderHelper)->setTemplate('fooman/pdfcustomiser/items.phtml')->toHtml();

            //Put it all together
            $pdf->writeHTML($top);
            $pdf->SetFont($orderHelper->getPdfFont(), '', $orderHelper->getPdfFontsize('small'));
            $pdf->writeHTML($items, true, false, false, false, '');
            $pdf->SetFont($orderHelper->getPdfFont(), '', $orderHelper->getPdfFontsize());
            //reset Margins in case there was a page break
            $pdf->setMargins($orderHelper->getPdfMargins('sides'), $orderHelper->getPdfMargins('top'));
            $pdf->writeHTML($bottom);

            if ($order->getStoreId()) {
                $appEmulation->stopEnvironmentEmulation($initial);
            }
            $pdf->setPdfAnyOutput(true);
        }

        //output PDF document
        if(!$suppressOutput) {
            if($pdf->getPdfAnyOutput()) {
                // reset pointer to the last page
                $pdf->lastPage();
                $pdf->Output(
                    preg_replace("/[^a-zA-Z]/", "", $orderHelper->getPdfTitle()) . '_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf',
                    $orderHelper->getNewWindow()
                );
                exit;
            }else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('There are no printable documents related to selected orders'));
            }
        }

        $this->_afterGetPdf();

        return $pdf;
    }



    public function addOrder($orderHelper, &$tbl,$orderId,$units,$pdf){


    }
}