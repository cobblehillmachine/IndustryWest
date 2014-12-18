<?php

class Fooman_PdfCustomiser_Block_Pdf_Items extends Fooman_PdfCustomiser_Block_Pdf_Abstract
{

    public function getColumnHeaders ()
    {
        return $this->getPdfHelper()->getPdfColumnHeaders();
    }
    
    public function getItemRow ($pdfItem, $vertSpacing, $styleOverride=false)
    {
        return $this->getPdfHelper()->getPdfItemRow($pdfItem, $vertSpacing, $styleOverride);
    }    
    
    public function getBundleItemRow ($pdfItem, $subItems, $vertSpacing, $styleOverride=false)
    {
        return $this->getPdfHelper()->getPdfBundleItemRow($pdfItem, $subItems, $vertSpacing, $styleOverride);
    }    
    
    public function getItems ()
    {
        return $this->getPdf()->getItems();
    }
    
    public function getBundleItems ()
    {
        return $this->getPdf()->getBundleItems();
    }

}