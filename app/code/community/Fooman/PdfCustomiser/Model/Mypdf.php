<?php

//load the tcpdf library
require_once(BP . DS . 'lib' . DS . 'tcpdf' . DS . 'tcpdf.php');

/*
 *  Extend the TCPDF class
 */

class Fooman_PdfCustomiser_Model_Mypdf extends TCPDF
{

    const FACTOR_PIXEL_PER_MM = 3;


    protected $_TaxTotal = array();
    protected $_TaxAmount = array();
    protected $_HiddenTaxAmount = 0;
    protected $_BaseHiddenTaxAmount = 0;
    protected $_pdfItems = array();
    protected $_pdfBundleItems = array();
    public $_shippingTaxRate = '';
    public $_surchargeTaxRate = '';

    /**
     * storeId
     * @access protected
     */
    protected $_storeId;

    /**
     * get storeId
     * @return  int
     * @access public
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * set storeId
     *
     * @param $id
     *
     * @return  void
     * @access public
     */
    public function setStoreId($id)
    {
        $this->_storeId = $id;
    }

    /**
     * helper
     * @access protected
     */
    protected $_pdfHelper;

    /**
     * get helper
     * @access public
     * @return Fooman_PdfCustomiser_Helper_Pdf
     */
    public function getPdfHelper()
    {
        return $this->_pdfHelper;
    }

    /**
     * set helper
     *
     * @param \Fooman_PdfCustomiser_Helper_Pdf $helper
     *
     * @return  void
     * @access public
     */
    public function setPdfHelper(Fooman_PdfCustomiser_Helper_Pdf $helper)
    {
        $this->_pdfHelper = $helper;
    }

    protected $_incrementId;

    public function getIncrementId()
    {
        return $this->_incrementId;
    }

    public function setIncrementId($id)
    {
        $this->_incrementId = $id;
    }

    /**
     * keep track if we have output
     * @access protected
     */
    protected $_PdfAnyOutput = false;

    /**
     * do we have output?
     * @return  bool
     * @access public
     */
    public function getPdfAnyOutput()
    {
        return $this->_PdfAnyOutput;
    }

    /**
     * set _PdfAnyOutput
     *
     * @param $flag
     *
     * @return  void
     * @access public
     */
    public function setPdfAnyOutput($flag)
    {
        $this->_PdfAnyOutput = $flag;
    }

    /**
     * retrieve line items
     *
     * @param \Fooman_PdfCustomiser_Helper_Pdf $helper
     * @param                                  $printItem
     * @param null                             $order
     *
     * @internal param $
     * @return void
     * @access   public
     */
    public function prepareLineItems(Fooman_PdfCustomiser_Helper_Pdf $helper, $printItem, $order = null)
    {
        $this->_TaxTotal = array();
        $this->_TaxAmount = array();
        $this->_HiddenTaxAmount = 0;
        $this->_pdfItems = array();
        $this->_pdfBundleItems = array();

        if (Mage::getStoreConfig('tax/sales_display/price', $helper->getStoreId()) == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH
            || Mage::getStoreConfig('tax/sales_display/price', $helper->getStoreId()) == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX
        ) {
            $displayItemTaxInclusive = true;
        } else {
            $displayItemTaxInclusive = false;
        }
        if (Mage::getStoreConfigFlag('sales_pdf/all/allrowtotaltaxinclusive', $helper->getStoreId())) {
            $displaySubtotalTaxInclusive = true;
        } else {
            $displaySubtotalTaxInclusive = false;
        }
        foreach ($printItem->getAllItems() as $item) {
            $pdfTemp = array();

            //check if we are printing an order
            if ($item instanceof Mage_Sales_Model_Order_Item) {
                $isOrderItem = true;
                $orderItem = $item;
                $pdfTemp['qty'] = $helper->getPdfQtyAsInt() ? (int)$item->getQtyOrdered() : $item->getQtyOrdered();
            } else {
                $isOrderItem = false;
                $orderItem = $item->getOrderItem();
                $pdfTemp['qty'] = $helper->getPdfQtyAsInt() ? (int)$item->getQty() : $item->getQty();
            }
            //we generally don't want to display subitems of configurable products etc but we do for bundled
            $type = $orderItem->getProductType();
            $itemId = $orderItem->getItemId();
            $parentType = 'none';
            $parentItemId = $orderItem->getParentItemId();

            if ($parentItemId) {
                $parentItem = Mage::getModel('sales/order_item')->load($parentItemId);
                $parentType = $parentItem->getProductType();
            }

            //Get item details
            $pdfTemp['itemId'] = $itemId;
            $pdfTemp['productId'] = $orderItem->getProductId();
            $pdfTemp['type'] = $type;
            $pdfTemp['parentType'] = $parentType;
            $pdfTemp['parentItemId'] = $parentItemId;
            $pdfTemp['productDetails'] = $this->getItemNameAndSku($item, $helper);
            $pdfTemp['productOptions'] = $orderItem->getProductOptions();
            $pdfTemp['giftMessage'] = $this->getGiftMessage($orderItem);
            if ($displayItemTaxInclusive) {
                if ($item->getPriceInclTax()) {
                    $pdfTemp['price'] = $item->getPriceInclTax();
                } elseif($pdfTemp['qty']) {
                    $pdfTemp['price'] = $item->getPrice() + ($item->getTaxAmount() + $item->getHiddenTaxAmount()) / $pdfTemp['qty'];
                } else {
                    $pdfTemp['price'] = $item->getPrice();
                }
            } else {
                $pdfTemp['price'] = $item->getPrice();
            }
            $pdfTemp['discountAmount'] = $item->getDiscountAmount();

            $pdfTemp['taxAmount'] = $item->getTaxAmount() + $item->getHiddenTaxAmount();
            $pdfTemp['rowTotal'] = $item->getRowTotal();
            if ($displayItemTaxInclusive || $displaySubtotalTaxInclusive) {
                if ($item->getRowTotalInclTax()) {
                    $pdfTemp['rowTotal'] += $pdfTemp['taxAmount'] - $pdfTemp['discountAmount'] - $item->getHiddenTaxAmount();
                } else {
                    $pdfTemp['rowTotal'] += $pdfTemp['taxAmount'];
                }
            }
            //get item details - BASE
            if ($displayItemTaxInclusive) {
                if ($item->getBasePriceInclTax()) {
                    $pdfTemp['basePrice'] = $item->getBasePriceInclTax();
                } elseif ($pdfTemp['qty']) {
                    $pdfTemp['basePrice'] = $item->getBasePrice() + ($item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount()) / $pdfTemp['qty'];
                } else {
                    $pdfTemp['basePrice'] = $item->getBasePrice();
                }
            } else {
                $pdfTemp['basePrice'] = $item->getBasePrice();
            }
            $pdfTemp['baseDiscountAmount'] = $item->getBaseDiscountAmount();
            $pdfTemp['baseTaxAmount'] = $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
            $pdfTemp['baseRowTotal'] = $item->getBaseRowTotal();
            if ($displayItemTaxInclusive || $displaySubtotalTaxInclusive) {
                if ($item->getRowTotalInclTax()) {
                    $pdfTemp['baseRowTotal'] += $pdfTemp['baseTaxAmount'] - $pdfTemp['baseDiscountAmount'] - $item->getBaseHiddenTaxAmount();
                } else {
                    $pdfTemp['baseRowTotal'] += $pdfTemp['baseTaxAmount'];
                }
            }

            if ($orderItem->getTaxPercent()) {
                $taxPercent = sprintf("%01.4f", $orderItem->getTaxPercent());
            } else {
                $taxPercent = '0.000';
            }
            $pdfTemp['taxPercent'] = sprintf("%01.2f", $taxPercent) . '%';
            if ($type == 'bundle') {
                $bundlePdfModel = Mage::getModel('bundle/sales_order_pdf_items_invoice');
                $bundlePdfModel->setItem($orderItem);
                $parentFixedPrice = $bundlePdfModel->isChildCalculated($orderItem);
                if (!$parentFixedPrice) {
                    isset($this->_TaxTotal[$taxPercent]) ? $this->_TaxTotal[$taxPercent] += $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseHiddenTaxAmount() : $this->_TaxTotal[$taxPercent] = $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseHiddenTaxAmount();
                    isset($this->_TaxAmount[$taxPercent]) ? $this->_TaxAmount[$taxPercent] += $item->getBaseTaxAmount() : $this->_TaxAmount[$taxPercent] = $item->getBaseTaxAmount();
                    $this->_HiddenTaxAmount += $item->getHiddenTaxAmount();
                    $this->_BaseHiddenTaxAmount += $item->getBaseHiddenTaxAmount();
                }
            } else {
                isset($this->_TaxTotal[$taxPercent]) ? $this->_TaxTotal[$taxPercent] += $item->getBaseRowTotal() - $item->getBaseDiscountAmount() : $this->_TaxTotal[$taxPercent] = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
                isset($this->_TaxAmount[$taxPercent]) ? $this->_TaxAmount[$taxPercent] += $item->getBaseTaxAmount() : $this->_TaxAmount[$taxPercent] = $item->getBaseTaxAmount();
                $this->_HiddenTaxAmount += $item->getHiddenTaxAmount();
                $this->_BaseHiddenTaxAmount += $item->getBaseHiddenTaxAmount();
            }

            //prepare image
            $pdfTemp['image'] = false;
            if ($helper->printProductImages()) {
                $productImage = Mage::getModel('catalog/product')->load($pdfTemp['productId'])->getImage();
                if ($parentItemId && $productImage == "no_selection") {
                    $productImage = $parentItem->getImage();
                }
                $imagePath = DS .'catalog' . DS . 'product' . $productImage;
                if (!$productImage || $productImage == "no_selection" || !file_exists(Mage::getBaseDir('media').$imagePath)) {
                    $pdfTemp['image'] = false;
                } else {
                    $pdfTemp['image'] = Mage::getBaseUrl('media').$imagePath;
                }
            }

            //collect bundle subitems separately
            if ($type == 'bundle') {
                $bundleHelper = Mage::helper('pdfcustomiser/bundle');

                if ($isOrderItem) {
                    $bundleChildren = $orderItem->getChildrenItems();
                } else {
                    $bundleChildren = $bundleHelper->getChilds($item);
                }

                if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                    $shipSeparate = $bundleHelper->isShipmentSeparately($item);
                } else {
                    $shipSeparate = false;
                }
                foreach ($bundleChildren as $childItem) {
                    if ($childItem->getId() == $item->getId()) {
                        continue;
                    }
                    $selectionAttributes = $bundleHelper->getSelectionAttributes($childItem);
                    $subBundleItem = array();
                    $subBundleItem['price'] = $childItem->getPrice();
                    $subBundleItem['parentItemId'] = $itemId;
                    if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                        if ($shipSeparate && isset($bundleChildren[$childItem->getId()])) {
                            $subBundleItem['qty'] = $helper->getPdfQtyAsInt() ? (int)$bundleChildren[$childItem->getId()]->getQty() : $bundleChildren[$childItem->getId()]->getQty();
                        } elseif ($shipSeparate) {
                            $subBundleItem['qty'] = $helper->getPdfQtyAsInt() ? (int)$selectionAttributes['qty'] : $selectionAttributes['qty'];
                        } else {
                            $subBundleItem['qty'] = $helper->getPdfQtyAsInt() ? (int)$childItem->getQty() : $childItem->getQty();
                        }
                        if (!$shipSeparate) {
                            $pdfTemp['qty'] = '';
                        }
                    } else {
                        if ($parentFixedPrice) {
                            $subBundleItem['qty'] = $helper->getPdfQtyAsInt() ? (int)$selectionAttributes['qty'] : $selectionAttributes['qty'];
                        } elseif ($isOrderItem) {
                            $subBundleItem['qty'] = $helper->getPdfQtyAsInt() ? (int)$childItem->getQtyOrdered() : $childItem->getQtyOrdered();
                        } else {
                            $subBundleItem['qty'] = $helper->getPdfQtyAsInt() ? (int)$childItem->getQty() : $childItem->getQty();
                        }
                    }

                    if ($displayItemTaxInclusive && $subBundleItem['qty'] != 0) {
                        $subBundleItem['price'] = $childItem->getPrice() + ($childItem->getTaxAmount() + $childItem->getHiddenTaxAmount()) / $subBundleItem['qty'];
                    } else {
                        $subBundleItem['price'] = $childItem->getPrice();
                    }
                    $subBundleItem['discountAmount'] = $childItem->getDiscountAmount();

                    $subBundleItem['taxAmount'] = $childItem->getTaxAmount() + $childItem->getHiddenTaxAmount();
                    $subBundleItem['rowTotal'] = $childItem->getRowTotal();
                    if ($displayItemTaxInclusive || $displaySubtotalTaxInclusive) {
                        $subBundleItem['rowTotal'] += $subBundleItem['taxAmount'];
                    }
                    //get item details - BASE
                    if ($displayItemTaxInclusive && $subBundleItem['qty'] != 0) {
                        $subBundleItem['basePrice'] = $childItem->getBasePrice() + ($childItem->getBaseTaxAmount() + $childItem->getBaseHiddenTaxAmount()) / $subBundleItem['qty'];
                    } else {
                        $subBundleItem['basePrice'] = $childItem->getBasePrice();
                    }
                    $subBundleItem['baseDiscountAmount'] = $childItem->getBaseDiscountAmount();
                    $subBundleItem['baseTaxAmount'] = $childItem->getBaseTaxAmount() + $childItem->getBaseHiddenTaxAmount();
                    $subBundleItem['baseRowTotal'] = $childItem->getBaseRowTotal();
                    if ($displayItemTaxInclusive || $displaySubtotalTaxInclusive) {
                        $subBundleItem['baseRowTotal'] += $subBundleItem['baseTaxAmount'];
                    }

                    if ($childItem->getTaxPercent()) {
                        $taxPercent = sprintf("%01.4f", $childItem->getTaxPercent());
                    } else {
                        $taxPercent = '0.000';
                    }
                    $subBundleItem['taxPercent'] = $taxPercent;
                    if ($helper->printProductImages()) {
                        $productImage = Mage::getModel('catalog/product')->load($childItem->getProductId())->getImage();
                        if ($productImage == "no_selection") {
                            $productImage = $item->getImage();
                        }
                        $imagePath = DS . 'catalog' . DS . 'product' . $productImage;
                        if (!$productImage || $productImage == "no_selection" || !file_exists(Mage::getBaseDir('media').$imagePath)) {
                            $subBundleItem['image'] = false;
                        } else {
                            $subBundleItem['image'] = Mage::getBaseUrl('media').$imagePath;
                        }
                    }

                    $subBundleItem['productDetails'] = $this->getItemNameAndSku($childItem, $helper);
                    if ($selectionAttributes['option_label']) {
                        $subBundleItem['productDetails']['Name'] = "<b>" . $selectionAttributes['option_label'] . "</b>: " . $subBundleItem['productDetails']['Name'];
                    }
                    $transport = new Varien_Object();
                    $transport->setItemData($subBundleItem);
                    Mage::dispatchEvent('fooman_pdfcustomiser_prepare_subbundleitem',
                        array(
                            'item'=> $childItem,
                            'transport' => $transport
                        )
                    );
                    $this->_pdfBundleItems[$itemId][] = $transport->getItemData();
                }
            }
            if ($parentType != 'bundle') {
                $transport = new Varien_Object();
                $transport->setItemData($pdfTemp);
                Mage::dispatchEvent('fooman_pdfcustomiser_prepare_item',
                    array(
                        'item'=> $item,
                        'transport' => $transport
                    )
                );
                $this->_pdfItems[$itemId] = $transport->getItemData();
            }
            //Mage::log($pdfTemp);
        }
        $this->_shippingTaxRate = 0;
        $this->_surchargeTaxRate = 0;
        if ($helper->displayTaxSummary() && $order) {
            $filteredTaxrates = array();
            //need to filter out doubled up taxrates on edited/reordered items -> Magento bug
            foreach ($order->getFullTaxInfo() as $taxrate) {
                foreach ($taxrate['rates'] as $rate) {
                    $taxId = $rate['code'];
                    if (!isset($rate['title'])) {
                        $rate['title'] = $taxId;
                    }
                    $filteredTaxrates[$taxId] = array('id' => $rate['code'], 'percent' => $rate['percent'], 'amount' => $taxrate['amount'], 'baseAmount' => $taxrate['base_amount'], 'title' => $rate['title']);
                }
            }

            //loop over tax amounts to find out which rate applies to shipping tax
            foreach ($filteredTaxrates as $taxId => $filteredTaxrate) {
                //Magento keeps no record of the associated tax rate for shipping
                //due to rounding we can only get to within reasonable approximation of the rate (0.0066 ^ 0.66%)
                if (abs((($printItem->getBaseShippingAmount() * $filteredTaxrate['percent']) / 100) - $printItem->getBaseShippingTaxAmount()) < 0.0066) {
                    $this->_shippingTaxRate = sprintf("%01.2f", $filteredTaxrate['percent']);
                    $taxPercent = sprintf("%01.4f", $this->_shippingTaxRate);
                    isset($this->_TaxTotal[$taxPercent]) ? $this->_TaxTotal[$taxPercent] += $printItem->getBaseShippingAmount() : $this->_TaxTotal[$taxPercent] = $printItem->getBaseShippingAmount();
                    isset($this->_TaxAmount[$taxPercent]) ? $this->_TaxAmount[$taxPercent] += $printItem->getBaseShippingTaxAmount() : $this->_TaxAmount[$taxPercent] = $printItem->getBaseShippingTaxAmount();
                }
                if (abs((($printItem->getBaseFoomanSurchargeAmount() * $filteredTaxrate['percent']) / 100) - $printItem->getBaseFoomanSurchargeTaxAmount()) < 0.0075) {
                    $this->_surchargeTaxRate = sprintf("%01.2f", $filteredTaxrate['percent']);
                    $taxPercent = sprintf("%01.4f", $this->_surchargeTaxRate);
                    isset($this->_TaxTotal[$taxPercent]) ? $this->_TaxTotal[$taxPercent] += $printItem->getBaseFoomanSurchargeAmount() : $this->_TaxTotal[$taxPercent] = $printItem->getBaseFoomanSurchargeAmount();
                    isset($this->_TaxAmount[$taxPercent]) ? $this->_TaxAmount[$taxPercent] += $printItem->getBaseFoomanSurchargeTaxAmount() : $this->_TaxAmount[$taxPercent] = $printItem->getBaseFoomanSurchargeTaxAmount();
                }
            }
        }
        if (abs($this->_shippingTaxRate) < 0.005 && $printItem->getBaseShippingAmount() > 0) {
            $zero = sprintf("%01.4f", 0);
            isset($this->_TaxTotal[$zero]) ? $this->_TaxTotal[$zero] += $printItem->getBaseShippingAmount() : $this->_TaxTotal[$zero] = $printItem->getBaseShippingAmount();
            isset($this->_TaxAmount[$zero]) ? $this->_TaxAmount[$zero] += $printItem->getBaseShippingTaxAmount() : $this->_TaxAmount[$zero] = $printItem->getBaseShippingTaxAmount();
        }
        if (abs($this->_surchargeTaxRate) < 0.005 && $printItem->getBaseFoomanSurchargeAmount() > 0) {
            $zero = sprintf("%01.4f", 0);
            isset($this->_TaxTotal[$zero]) ? $this->_TaxTotal[$zero] += $printItem->getBaseFoomanSurchargeAmount() : $this->_TaxTotal[$zero] = $printItem->getBaseFoomanSurchargeAmount();
            isset($this->_TaxAmount[$zero]) ? $this->_TaxAmount[$zero] += $printItem->getBaseFoomanSurchargeTaxAmount() : $this->_TaxAmount[$zero] = $printItem->getBaseFoomanSurchargeTaxAmount();
        }
        //Mage::log('$this->_shippingTaxRate'.$this->_shippingTaxRate);
        //Mage::log($this->_TaxAmount);
        //Mage::log($this->_TaxTotal);
    }

    public function getItems()
    {
        return $this->_pdfItems;
    }

    public function getBundleItems()
    {
        return $this->_pdfBundleItems;
    }

    public function getTaxTotal()
    {
        return $this->_TaxTotal;
    }

    public function getTaxAmount()
    {
        return $this->_TaxAmount;
    }

    /*
     * Page header
     * return float height of logo
     */

    public function printHeader(Fooman_PdfCustomiser_Helper_Pdf $helper, $title, $incrementId = false)
    {

        if ($incrementId) {
            $style = array();
            //$style = array('text' => true, 'fontsize'=>8);
            parent::write1DBarcode($incrementId, 'C39E+', $helper->getPdfMargins('sides'), 5, 50, 5, '', $style);
            $this->SetXY($helper->getPdfMargins('sides'), $helper->getPdfMargins('top'));
        }
        // Place Logo
        if ($helper->getPdfLogo()) {
            if ($helper->getPdfLogoPlacement() == 'auto') {
                $maxLogoHeight = 25;
                $currentY = $this->GetY();
                //Figure out if logo is too wide - half the page width minus margins
                $maxWidth = ($helper->getPageWidth() / 2) - $helper->getPdfMargins('sides');
                if ($helper->getPdfLogoDimensions('w') > $maxWidth) {
                    $logoWidth = $maxWidth;
                } else {
                    $logoWidth = $helper->getPdfLogoDimensions('w');
                }
                //centered
                //$this->Image($helper->getPdfLogo(), $this->getPageWidth()/2  + (($this->getPageWidth()/2 - $helper->getPdfMargins('sides') - $logoWidth)/2), $helper->getPdfMargins('top'), $logoWidth, $maxLogoHeight, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=true);
                $this->Image($helper->getPdfLogo(), $this->getPageWidth() / 2, $helper->getPdfMargins('top'), $logoWidth, $maxLogoHeight, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = true);
                $helper->setImageHeight($this->getImageRBY() + 3 - $currentY);
            } elseif ($helper->getPdfLogoPlacement() == 'auto-right') {
                $maxLogoHeight = 25;
                $currentY = $this->GetY();

                //Figure out if logo is too wide - half the page width minus margins
                $maxWidth = ($helper->getPageWidth() / 2) - $helper->getPdfMargins('sides');
                if ($helper->getPdfLogoDimensions('w') > $maxWidth) {
                    $logoWidth = $maxWidth;
                } else {
                    $logoWidth = $helper->getPdfLogoDimensions('w');
                }
                //centered
                //$this->Image($helper->getPdfLogo(), $this->getPageWidth()/2  + (($this->getPageWidth()/2 - $helper->getPdfMargins('sides') - $logoWidth)/2), $helper->getPdfMargins('top'), $logoWidth, $maxLogoHeight, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=true);
                $this->Image($helper->getPdfLogo(), $helper->getPdfMargins('sides'), $helper->getPdfMargins('top'), $logoWidth, $maxLogoHeight, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = true);
                $helper->setImageHeight($this->getImageRBY() + 3 - $currentY);
            } elseif ($helper->getPdfLogoPlacement() == 'no-scaling') {
                $currentY = $this->GetY();
                $this->Image($helper->getPdfLogo(), $this->getPageWidth() / 2, $helper->getPdfMargins('top'), $helper->getPdfLogoDimensions('w'), $helper->getPdfLogoDimensions('h'), $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false);
                $helper->setImageHeight($this->getImageRBY() + 3 - $currentY);
            } elseif ($helper->getPdfLogoPlacement() == 'no-scaling-right') {
                $currentY = $this->GetY();
                $this->Image($helper->getPdfLogo(), $helper->getPdfMargins('sides'), $helper->getPdfMargins('top'), $helper->getPdfLogoDimensions('w'), $helper->getPdfLogoDimensions('h'), $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false);
                $helper->setImageHeight($this->getImageRBY() + 3 - $currentY);
            } else {
                $currentY = $this->GetY();
                $coords = $helper->getPdfLogoCoords();
                $this->Image($helper->getPdfLogo(), $coords['x'], $coords['y'], $coords['w'] * self::FACTOR_PIXEL_PER_MM, $coords['h'] * self::FACTOR_PIXEL_PER_MM, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = true);
                $helper->setImageHeight($this->getImageRBY() + 3 - $currentY);
            }
        } else {
            $helper->setImageHeight(false);
        }
    }

    /*
     *  set some standards for all pdf pages
     */
    public function SetStandard(Fooman_PdfCustomiser_Helper_Pdf $helper)
    {

        // set document information
        $this->SetCreator('Magento');

        //set margins
        $this->SetMargins($helper->getPdfMargins('sides'), $helper->getPdfMargins('top'));

        // set header and footer
        $printNumbers = Mage::getStoreConfig('sales_pdf/all/allpagenumbers', $helper->getStoreId());
        $this->setPrintFooter($printNumbers || $helper->hasFooter());
        $this->setPrintHeader(true);

        $this->setHeaderMargin(0);
        $this->setFooterMargin($helper->getPdfMargins('bottom'));

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set auto page breaks
        $this->SetAutoPageBreak(true, $helper->getPdfMargins('bottom') + 10);

        //set image scale factor 3 pixels = 1mm
        $this->setImageScale(self::FACTOR_PIXEL_PER_MM);

        //set image quality
        $this->setJPEGQuality(95);

        //uncomment for quicker file generation when not using core fonts
        //downside is increased file size
        //$this->setFontSubsetting(false);

        // set font
        $this->SetFont($helper->getPdfFont(), '', $helper->getPdfFontsize());

        // set fillcolor black
        $this->SetFillColor(0);

        // see if we need to sign
        if (Mage::getStoreConfig('sales_pdf/all/allsign', $helper->getStoreId())) {
            $certificate = Mage::helper('core')->decrypt(Mage::getStoreConfig('sales_pdf/all/allsigncertificate', $helper->getStoreId()));
            $certpassword = Mage::helper('core')->decrypt(Mage::getStoreConfig('sales_pdf/all/allsignpassword', $helper->getStoreId()));

            // set document signature
            $this->setSignature($certificate, $certificate, $certpassword, '', 2, null);
        }

        //set Right to Left Language
        if (Mage::app()->getLocale()->getLocaleCode() == 'he_IL' || Mage::app()->getLocale()->getLocaleCode() == 'ar_SA') {
            $this->setRTL(true);
            $helper->setParameter(0, 'rtl', true);
        } else {
            $this->setRTL(false);
            $helper->setParameter(0, 'rtl', false);
        }
        $this->startPageGroup();

    }

    public function Header()
    {
        $helper = $this->getPdfHelper();
        $helper->setStoreId($this->getStoreId());
        if (!$helper->getPdfBgOnlyFirst() || ($helper->getPdfBgOnlyFirst() && isset($this->newpagegroup[$this->page]))) {
            $imagePath = $helper->getPdfBgImage();
            if (file_exists($imagePath)) {
                $this->SetAutoPageBreak(false, 0);
                $this->Image($imagePath, 0, 0, $this->getPageWidth(),
                    $this->getPageHeight(), $type = '', $link = '', $align = '',
                    $resize = false, $dpi = 300, $palign = '', $ismask = false,
                    $imgmask = false, $border = 0, $fitbox = true, $hidden = false);
                $this->SetAutoPageBreak(true, $helper->getPdfMargins('bottom'));
            }
        }
        // Line break
        $this->Ln();

    }

    public function Footer()
    {
        $helper = $this->getPdfHelper();
        $helper->setStoreId($this->getStoreId());
        $footers = $helper->getFooters();

        if ($footers[0] > 0) {
            $marginBetween = 5;
            $width = ($this->getPageWidth() - 2 * $helper->getPdfMargins('sides') - ($footers[0] - 1) * $marginBetween) / $footers[0];
            $this->SetFont($helper->getPdfFont(), '', $helper->getPdfFontsize('small'));
            $html = '<table><tr>';
            foreach ($footers as $key => $footer) {
                //don't display first element
                if ($key > 0) {
                    if ($key < $footers[0]) {
                        //not last element
                        $html .= '<td width="'.$width.'mm">'.$footer.'</td>';
                        $html .= '<td width="'.$marginBetween.'mm"></td>';
                    } elseif ($key == $footers[0]) {
                        //last element
                        if (!empty($footer)) {
                            $html .= '<td width="'.$width.'mm">'.$footer.'</td>';
                        }
                    }
                }
            }
            $html .= '</tr></table>';
            $this->writeHTML($html);
            $this->SetFont($helper->getPdfFont(), '', $helper->getPdfFontsize(''));
        }
        if (Mage::getStoreConfig('sales_pdf/all/allpagenumbers', $helper->getStoreId())) {
            $this->MultiCell(0, 0, $this->getPageNumGroupAlias() . ' / ' . $this->getPageGroupAlias(), 0, 'C', 0, 1);
            //$this->MultiCell(($this->getPageWidth()- 2* $helper->getPdfMargins('sides'))/2, 0, $this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias(), 0, 'L', 0, 0);
            //$this->MultiCell(0, 0, $this->getIncrementId(), 0, 'R', 0, 1);        
        }
    }

    public function Line2($space = 1)
    {
        $this->SetY($this->GetY() + $space);
        $margins = $this->getMargins();
        $this->Line($margins['left'], $this->GetY(), $this->getPageWidth() - $margins['right'], $this->GetY());
        $this->SetY($this->GetY() + $space);

    }

    /*
     *  get product name and Sku, take into consideration configurable products and product options
     */
    public function getItemNameAndSku($item, $helper)
    {
        $return = array();
        $return['Name'] = htmlentities($item->getName(), ENT_QUOTES, 'UTF-8', false);
        $return['Sku'] = htmlentities($item->getSku(), ENT_QUOTES, 'UTF-8', false);
        //$return['Name'] = $item->getName();
        //$return['Sku'] = $item->getSku();
        $return['Options'] = '';
        $return['Subitems'] = false;

        //check if we are printing an non-order = item has a method getOrderItem
        if (method_exists($item, 'getOrderItem')) {
            $item = $item->getOrderItem();
        }
        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                foreach ($options['options'] as $option) {
                    if ($option['label'] == 'Detail') {
                        foreach (explode("\n", $option['value']) as $detailLines) {
                            $return['Options'] .= "<br/>&nbsp;&nbsp;" . htmlentities($detailLines, ENT_QUOTES, 'UTF-8', false);
                        }
                    } else {
                        $return['Options'] .= "<br/>&nbsp;&nbsp;" . htmlentities($option['label'] . ": " . $option['value'], ENT_QUOTES, 'UTF-8', false);
                    }
                }
                $return['Options'] .= "<br/>";
            }
            if (isset($options['additional_options'])) {
                foreach ($options['additional_options'] as $additionalOption) {
                    $return['Options'] .= "<br/>&nbsp;&nbsp;" . htmlentities($additionalOption['label'] . ": " . $additionalOption['value'], ENT_QUOTES, 'UTF-8', false);
                }
                $return['Options'] .= "<br/>";
            }
            if (isset($options['attributes_info'])) {
                foreach ($options['attributes_info'] as $attribute) {
                    $return['Options'] .= "<br/>&nbsp;&nbsp;" . htmlentities($attribute['label'] . ": " . $attribute['value'], ENT_QUOTES, 'UTF-8', false);
                }
            }
            if ($item->getProductType() == 'ugiftcert') {
                foreach (Mage::helper('ugiftcert')->getGiftcertOptionVars() as $attribute => $label) {
                    if (isset($options['info_buyRequest'][$attribute]) && !empty($options['info_buyRequest'][$attribute])) {
                        $return['Options'] .= "<br/>&nbsp;&nbsp;" . $label . ": " . Mage::helper('core')->htmlEscape($options['info_buyRequest'][$attribute]);
                    }
                }
            }
            if ($item->getProductOptionByCode('simple_sku')) {
                $return['Sku'] = $item->getProductOptionByCode('simple_sku');
            }
        }
        $attributeCode = $helper->getCustomColumnAttribute();
        if ($attributeCode) {

            //load the product via sku here, will display the custom attribute of the
            //simple product attached to a configurable
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$return['Sku']);
            if ($product) {
                $product->getAttributes();
                $return['custom'] = $product->getAttributeText($attributeCode);
                if (!$return['custom']) {
                    $return['custom'] = $product->getDataUsingMethod($attributeCode);
                }
            } else {
                $return['custom'] = '';
            }

        }

        /*
        //Uncomment this block: delete /* and * / and enter your attribute code below
        $attributeCode ='attribute_code_from_Magento_backend';
        $productAttribute = Mage::getModel('catalog/product')->load($item->getProductId())->getData($attributeCode);
        if(!empty($productAttribute)){
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
            $return['Name'] .= "<br/><br/>".$attribute->getFrontendLabel().": ".$productAttribute;
        }
         */
        return $return;
    }


    /**
     * @param Fooman_PdfCustomiser_Helper_Pdf $helper
     * @param                                 $order
     * @param                                 $which
     *
     * @return mixed|string
     */
    public function PrepareCustomerAddress(Fooman_PdfCustomiser_Helper_Pdf $helper, $order, $which)
    {

        if (version_compare(Mage::getVersion(), '1.4.2.0') < 0) {
            $format = Mage::getStoreConfig('sales_pdf/all/alladdressformat', $helper->getStoreId());
        } else {
            $format = 'pdf';
        }
        if ($which == 'billing') {
            $billingAddress = $this->_fixAddress($order->getBillingAddress()->format($format));
            if ($order->getCustomerTaxvat()) {
                $billingAddress .= "<br/>" . Mage::helper('sales')->__('TAX/VAT Number') . ": " . $order->getCustomerTaxvat();
            } elseif (!$order->getCustomerIsGuest()) {
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if ($customer->getTaxvat()) {
                    $billingAddress .= "<br/>" . Mage::helper('sales')->__('TAX/VAT Number') . ": " . $customer->getTaxvat();
                }
            }
            // show the email address underneath the billing address
            if (Mage::getStoreConfig('sales_pdf/all/alldisplayemail', $helper->getStoreId())) {
                $billingAddress .= "<br/>" . $order->getCustomerEmail();
            }
            $billingAddress = str_replace("|", "<br/>", $billingAddress);
            return $billingAddress;
        } else {
            if (!$order->getIsVirtual()) {
                $shippingAddress = $this->_fixAddress($order->getShippingAddress()->format($format));
            } else {
                $shippingAddress = '';
            }
            $shippingAddress = str_replace("|", "<br/>", $shippingAddress);
            return $shippingAddress;
        }
    }

    /**
     *  output customer addresses
     *
     * @param \Fooman_PdfCustomiser_Helper_Pdf $helper
     * @param                                  $order
     * @param                                  $which
     */
    public function OutputCustomerAddresses(Fooman_PdfCustomiser_Helper_Pdf $helper, $order, $which)
    {
        $shippingAddress = $this->PrepareCustomerAddress($helper, $order, 'shipping');
        $billingAddress = $this->PrepareCustomerAddress($helper, $order, 'billing');

        //which addresses are we supposed to show
        switch ($which) {
            case 'both':
                //swap order for Packing Slips - shipping on the left
                if ($helper instanceof Fooman_PdfCustomiser_Helper_Pdf_Shipment) {
                    $this->SetX($helper->getPdfMargins('sides') + 5);
                    $this->Cell($this->getPageWidth() / 2 - $helper->getPdfMargins('sides'), 0, Mage::helper('sales')->__('SHIP TO:'), 0, 0, 'L');
                    if (!$order->getIsVirtual()) {
                        $this->Cell(0, 0, Mage::helper('sales')->__('SOLD TO:'), 0, 1, 'L');
                    } else {
                        $this->Cell(0, $this->getLastH(), '', 0, 1, 'L');
                    }
                    $this->SetX($helper->getPdfMargins('sides') + 10);
                    $this->writeHTMLCell($this->getPageWidth() / 2 - $helper->getPdfMargins('sides'), 0, null, null, $shippingAddress, null, 0);
                    if (!$order->getIsVirtual()) {
                        $this->writeHTMLCell(0, $this->getLastH(), null, null, $billingAddress, null, 1);
                    } else {
                        $this->Cell(0, $this->getLastH(), '', 0, 1, 'L');
                    }
                    $this->Ln(10);
                    break;
                } else {
                    $this->SetX($helper->getPdfMargins('sides') + 5);
                    $this->Cell($this->getPageWidth() / 2 - $helper->getPdfMargins('sides'), 0, Mage::helper('sales')->__('SOLD TO:'), 0, 0, 'L');
                    if (!$order->getIsVirtual()) {
                        $this->Cell(0, 0, Mage::helper('sales')->__('SHIP TO:'), 0, 1, 'L');
                    } else {
                        $this->Cell(0, $this->getLastH(), '', 0, 1, 'L');
                    }
                    $this->SetX($helper->getPdfMargins('sides') + 10);
                    $this->writeHTMLCell($this->getPageWidth() / 2 - $helper->getPdfMargins('sides'), 0, null, null, $billingAddress, null, 0);
                    if (!$order->getIsVirtual()) {
                        $this->writeHTMLCell(0, $this->getLastH(), null, null, $shippingAddress, null, 1);
                    } else {
                        $this->Cell(0, $this->getLastH(), '', 0, 1, 'L');
                    }
                    $this->Ln(10);
                    break;
                }

            case 'billing':
                $this->SetX($helper->getPdfMargins('sides') + 5);
                $this->writeHTMLCell(0, 0, null, null, $billingAddress, null, 1);
                $this->Ln(10);
                break;
            case 'shipping':
                $this->SetX($helper->getPdfMargins('sides') + 5);
                if (!$order->getIsVirtual()) {
                    $this->writeHTMLCell(0, 0, null, null, $shippingAddress, null, 1);
                }
                $this->Ln(10);
                break;
            case 'singlebilling':
                $this->SetAutoPageBreak(false, 85);
                $this->SetXY(-180, -67);
                $this->writeHTMLCell(75, 0, null, null, $billingAddress, null, 0);
                $this->SetAutoPageBreak(true, 85);
                break;
            case 'singleshipping':
                $this->SetAutoPageBreak(false, 85);
                $this->SetXY(-180, -67);
                if (!$order->getIsVirtual()) {
                    $this->writeHTMLCell(75, $this->getLastH(), null, null, $shippingAddress, null, 1);
                }
                $this->SetAutoPageBreak(true, 85);
                break;
            case 'double':
                $this->SetAutoPageBreak(false, 85);
                $this->SetXY(-180, -67);
                $this->writeHTMLCell(75, 0, null, null, $billingAddress, null, 0);
                $this->SetXY(-95, -67);
                if (!$order->getIsVirtual()) {
                    $this->writeHTMLCell(75, $this->getLastH(), null, null, $shippingAddress, null, 1);
                }
                $this->SetAutoPageBreak(true, 85);
                break;
            case 'doublereturn':
                $this->SetAutoPageBreak(false);
                $this->MultiCell(75, 47, Mage::helper('pdfcustomiser')->__('Return Address') . ":\n\n" . $helper->getPdfOwnerAddresss(), 0, 'L', 0, 0, 30, 230);
                if (!$order->getIsVirtual()) {
                    $this->writeHTMLCell(75, 47, 115, 230, $shippingAddress, null, 0);
                }
                $this->SetAutoPageBreak(true, 85);
                break;
            default:
                $this->SetX($helper->getPdfMargins('sides') + 5);
                $this->writeHTMLCell(0, 0, null, null, $billingAddress, null, 1);
                $this->Ln(10);
        }
    }

    /**
     * Prepare the payment info as html
     *
     * @param Fooman_PdfCustomiser_Helper_Pdf $helper
     * @param                                 $order
     * @param                                 $printItem
     *
     * @return mixed
     * @throws Exception
     */
    public function PreparePayment(Fooman_PdfCustomiser_Helper_Pdf $helper, $order, $printItem)
    {
        //save current area - then set to admin
        $oldArea = Mage::getDesign()->getArea();
        $oldPackageName = Mage::getDesign()->getPackageName();
        Mage::getDesign()->setArea('adminhtml')->setPackageName('default');

        //try if template exists in admin for pdf
        try {
            $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
                ->setIsSecureMode(true)
                ->toPdf();
            if (!$paymentInfo) {
                throw new Exception('empty payment method - try toHtml method');
            }
            //unfortunately not all payment methods supply a file/method, fall back on standard html output
        } catch (Exception $e) {
            Mage::getDesign()->setArea($oldArea)->setPackageName($oldPackageName);
            $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
                ->setIsSecureMode(true)
                ->toHtml();
        }

        Mage::getDesign()->setArea($oldArea)->setPackageName($oldPackageName);
        $paymentInfo = str_replace("{{pdf_row_separator}}", "<br/>", $paymentInfo);
        return $paymentInfo;
    }

    /**
     * Prepare shipping info as html
     *
     * @param Fooman_PdfCustomiser_Helper_Pdf $helper
     * @param                                 $order
     * @param                                 $printItem
     *
     * @return string
     */

    public function PrepareShipping(Fooman_PdfCustomiser_Helper_Pdf $helper, $order, $printItem)
    {
        if (!$order->getIsVirtual()) {
            $trackingInfo = "";
            $tracks = $order->getTracksCollection();
            if (count($tracks)) {
                $trackingInfo = "\n";
                foreach ($tracks as $track) {
                    if ($track->getNumber()) {
                        $trackingInfo .= "\n" . $track->getTitle() . ": " . $track->getNumber();
                    }
                }
            }
            //display depending on if Total Weight should be displayed or not
            if ($helper->displayWeight()) {
                //calculate weight
                $totalWeight = 0;
                foreach ($printItem->getAllItems() as $item) {
                    if ($printItem instanceof Mage_Sales_Model_Order) {
                        $totalWeight += $item->getQtyOrdered() * $item->getWeight();
                    } else {
                        $totalWeight += $item->getQty() * $item->getOrderItem()->getWeight();
                    }
                }
                //Output Shipping description with tracking info and Total Weight
                return $order->getShippingDescription() . $trackingInfo . "\n\n" . Mage::helper('pdfcustomiser')->__('Total Weight') . ': ' . $totalWeight . ' ' . Mage::getStoreConfig('sales_pdf/all/allweightunit', $helper->getStoreId());
            } else {
                return $order->getShippingDescription() . $trackingInfo;
            }
        } else {
            return '';
        }
    }


    /**
     * Output shipping and payment info to pdf
     *
     * @param Fooman_PdfCustomiser_Helper_Pdf $helper
     * @param                                 $order
     * @param                                 $printItem
     */
    public function OutputPaymentAndShipping(Fooman_PdfCustomiser_Helper_Pdf $helper, $order, $printItem)
    {

        $paymentInfo = $this->PreparePayment($helper, $order, $printItem);
        $shippingInfo = $this->PrepareShipping($helper, $order, $printItem);

        $this->SetFont($helper->getPdfFont(), 'B', $helper->getPdfFontsize());
        $this->Cell(0.5 * ($this->getPageWidth() - 2 * $helper->getPdfMargins('sides')), 0, Mage::helper('sales')->__('Payment Method'), 0, 0, 'L');
        if (!$order->getIsVirtual()) {
            $this->Cell(0, 0, Mage::helper('sales')->__('Shipping Method'), 0, 1, 'L');
        } else {
            $this->Cell(0, 0, '', 0, 1, 'L');
        }

        $this->SetFont($helper->getPdfFont(), '', $helper->getPdfFontsize());
        $this->writeHTMLCell(0.5 * ($this->getPageWidth() - 2 * $helper->getPdfMargins('sides')), 0, null, null, $paymentInfo, 0, 0);
        $this->MultiCell(0, $this->getLastH(), $shippingInfo, 0, 'L', 0, 1);
        $this->Cell(0, 0, '', 0, 1, 'L');
    }

    /**
     * return Gift Message as Array for order item
     *
     * @param $item
     *
     * @return array
     */
    public function getGiftMessage($item)
    {
        $returnArray = array();
        $returnArray['title'] = '';
        $returnArray['from'] = '';
        $returnArray['to'] = '';
        $returnArray['message'] = '';
        if ($item->getGiftMessageId() && $giftMessage = Mage::helper('giftmessage/message')->getGiftMessage($item->getGiftMessageId())) {
            $returnArray['from'] = htmlspecialchars($giftMessage->getSender());
            $returnArray['to'] =  htmlspecialchars($giftMessage->getRecipient());
            $returnArray['message'] = htmlspecialchars($giftMessage->getMessage());
        }
        return $returnArray;
    }

    /**
     * override parent function to change default style
     *
     * @param        $code
     * @param        $type
     * @param string $x
     * @param string $y
     * @param string $w
     * @param string $h
     * @param float  $xres
     * @param string $style
     * @param string $align
     */
    public function write1DBarcode($code, $type, $x = '', $y = '', $w = '', $h = '', $xres = 0.4, $style = '', $align = 'T')
    {
        $this->SetX($this->GetX()+4);
        $style = array(
            'position' => 'S',
            'border' => false,
            'padding' => 1,
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false,
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );
        parent::write1DBarcode($code, $type, $x, $y, $w, $h, $xres, $style, $align);
    }

    /**
     * see above - used for outputting barcodes of bundled products - smaller and no sku as text
     */
    public function write1DBarcode2($code, $type, $x = '', $y = '', $w = '', $h = '', $xres = 0.4, $style = '', $align = 'T')
    {
        $this->SetX($this->GetX()+4);
        $style = array(
            'position' => 'S',
            'border' => false,
            'padding' => 1,
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false,
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );
        parent::write1DBarcode($code, $type, $x, $y, $w, $h, $xres, $style, $align);
    }

    /**
     * replace any htmlspecialchars from input address except <br/>
     *
     * @param string $address
     *
     * @return string
     */
    private function _fixAddress($address)
    {
        $address = htmlspecialchars($address);
        $pattern = array('&lt;br/&gt;', '&lt;br /&gt;');
        $replacement = array('<br/>', '<br/>');
        return str_replace($pattern, $replacement, $address);
    }
}
