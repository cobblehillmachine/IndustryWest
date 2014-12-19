<?php

class Fooman_PdfCustomiser_Block_Pdf_Block extends Fooman_PdfCustomiser_Block_Pdf_Abstract
{
    public function OutputTaxSummary()
    {
        return $this->getPdfHelper()->OutputTaxSummary($this->getPdf()->getTaxTotal(), $this->getPdf()->getTaxAmount());
    }
}