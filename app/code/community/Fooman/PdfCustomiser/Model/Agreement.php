<?php

class Fooman_PdfCustomiser_Model_Agreement
{

    public function getPdf($agreement, $storeId)
    {
        $pdf = Mage::getModel('pdfcustomiser/mypdf', array('P', 'mm', Mage::getStoreConfig('sales_pdf/all/allpagesize', $storeId), true, 'UTF-8', false));

        // create new helper
        $helper = Mage::helper('pdfcustomiser/pdf_order');

        if ($storeId) {
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initial = $appEmulation->startEnvironmentEmulation($storeId,Mage_Core_Model_App_Area::AREA_FRONTEND,true);
        }

        $helper->setStoreId($storeId);
        $pdf->setStoreId($storeId);
        $pdf->setPdfHelper($helper);
        // set standard pdf info
        $pdf->SetStandard($helper);
        $pdf->addPage();
        if ($agreement->getIsHtml()) {
            $pdf->writeHTML($agreement->getContent());
        } else {
            $align = $helper->isRtl()?'R':'L';
            $pdf->MultiCell(0, 0, $agreement->getContent(), 0, $align, 0, 1);
        }
        $pdf->lastPage();
        return $pdf;
    }

}