<?php

class Fooman_PdfCustomiser_Model_Creditmemo extends Fooman_PdfCustomiser_Model_Abstract
{
    /**
    * Creates PDF using the tcpdf library from array of creditmemos or orderIds
    * @param array creditmemosGiven, $orderIds
    * @access public
    */
    public function getPdf($creditmemosGiven = array(),$orderIds = array(), $pdf = null, $suppressOutput = false, $outputFileName='')
    {

        if(empty($pdf) && empty($creditmemosGiven) && empty($orderIds)){
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('There are no printable documents related to selected orders'));
                return false;
        }

        //we will be working through an array of orderIds later - fill it up if only creditmemos is given
        if(!empty($creditmemosGiven)){
            foreach ($creditmemosGiven as $creditmemoGiven) {
                    $currentOrderId = $creditmemoGiven->getOrder()->getId();
                    $orderIds[] = $currentOrderId;
                    $creditmemoIds[$currentOrderId]=$creditmemoGiven->getId();
            }
        }

        $this->_beforeGetPdf();

        // create new creditmemo helper
        $creditmemoHelper = Mage::helper('pdfcustomiser/pdf_creditmemo');

        $storeId = $order = Mage::getModel('sales/order')->load($orderIds[0])->getStoreId();

        //work with a new pdf or add to existing one
        if(empty($pdf)){
            $pdf = Mage::getModel('pdfcustomiser/mypdf', array('P', 'mm',  Mage::getStoreConfig('sales_pdf/all/allpagesize',$storeId), true, 'UTF-8', false));
        }

        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if(!empty($creditmemosGiven)){
                $creditmemos = Mage::getResourceModel('sales/order_creditmemo_collection')
                    ->addAttributeToSelect('*')
                    ->setOrderFilter($orderId)
                    ->addAttributeToFilter('entity_id', $creditmemoIds[$orderId])
                    ->load();
            }else{
                $creditmemos = Mage::getResourceModel('sales/order_creditmemo_collection')
                    ->addAttributeToSelect('*')
                    ->setOrderFilter($orderId)
                    ->load();
            }
            if ($creditmemos->getSize() > 0) {
                foreach ($creditmemos as $creditmemo) {
                    $creditmemo->load($creditmemo->getId());
                    $storeId = $creditmemo->getStoreId();
                    if ($storeId) {
                        $appEmulation = Mage::getSingleton('core/app_emulation');
                        $initial = $appEmulation->startEnvironmentEmulation($storeId,Mage_Core_Model_App_Area::AREA_FRONTEND,true);
                    }

                    $creditmemoHelper->setStoreId($storeId);
                    $creditmemoHelper->setSalesObject($creditmemo);
                    $pdf->setStoreId($storeId);
                    $pdf->setPdfHelper($creditmemoHelper);
                    // set standard pdf info
                    $pdf->SetStandard($creditmemoHelper);

                    // add a new page
                    $pdf->AddPage();
                    $pdf->setIncrementId($creditmemo->getIncrementId());

                    // Print the logo
                    $pdf->printHeader($creditmemoHelper, $creditmemoHelper->getPdfTitle());

                    // Prepare Line Items
                    $pdf->prepareLineItems($creditmemoHelper, $creditmemo, $order);

                    // Prepare Top
                    $top = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_block')->setPdfHelper($creditmemoHelper)->setTemplate('fooman/pdfcustomiser/creditmemo/top.phtml')->toHtml();

                    $processor = Mage::helper('cms')->getBlockTemplateProcessor();
                    $processor->setVariables(
                            array(
                                'order' => $order,
                                'sales_object' => $creditmemo,                        
                                'billing_address'=> $pdf->PrepareCustomerAddress($creditmemoHelper, $order, 'billing'),
                                'shipping_address'=> $pdf->PrepareCustomerAddress($creditmemoHelper, $order, 'shipping'),
                                'payment'=> $pdf->PreparePayment ($creditmemoHelper, $order, $creditmemo),
                                'shipping'=> $pdf->PrepareShipping ($creditmemoHelper, $order, $creditmemo)
                            )
                    );
                    $top = $processor->filter($top);

                    //Prepare Totals
                    $totals = $this->PrepareTotals($creditmemoHelper, $creditmemo);

                    //Prepare Bottom
                    $bottom = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_block')
                            ->setPdf($pdf)
                            ->setPdfHelper($creditmemoHelper)
                            ->setTotals($totals)
                            ->setTemplate('fooman/pdfcustomiser/creditmemo/bottom.phtml')
                            ->toHtml();            
                    $processor->setVariables(
                            array(
                                'order' => $order,
                                'sales_object' => $creditmemo
                            )
                    );            
                    $bottom = $processor->filter($bottom);

                    //Prepare Items
                    $items = Mage::app()->getLayout()->createBlock('pdfcustomiser/pdf_items')->setPdf($pdf)->setPdfHelper($creditmemoHelper)->setTemplate('fooman/pdfcustomiser/items.phtml')->toHtml();

                    //Put it all together
                    $pdf->writeHTML($top);
                    $pdf->SetFont($creditmemoHelper->getPdfFont(), '', $creditmemoHelper->getPdfFontsize('small'));
                    $pdf->writeHTML($items, true, false, false, false, '');
                    $pdf->SetFont($creditmemoHelper->getPdfFont(), '', $creditmemoHelper->getPdfFontsize());
                    //reset Margins in case there was a page break
                    $pdf->setMargins($creditmemoHelper->getPdfMargins('sides'), $creditmemoHelper->getPdfMargins('top'));
                    $pdf->writeHTML($bottom); 
                    
                    if ($creditmemo->getStoreId()) {
                        $appEmulation->stopEnvironmentEmulation($initial);
                    }
                    $pdf->setPdfAnyOutput(true);
                 }
            }
        }

        //output PDF document
        if(!$suppressOutput) {
            if($pdf->getPdfAnyOutput()) {
                if (empty($outputFileName)) {
                    $outputFileName = preg_replace("/[^a-zA-Z]/", "", $creditmemoHelper->getPdfTitle());
                }
                // reset pointer to the last page
                $pdf->lastPage();
                $pdf->Output(
                    $outputFileName . '_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf',
                    $creditmemoHelper->getNewWindow()
                );
                exit;
            }else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('There are no printable documents related to selected orders'));
            }
        }

        $this->_afterGetPdf();
        return $pdf;
    }

}