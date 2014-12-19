<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

class Fooman_Connect_Model_Observer
{

    public function addXeroRate ($observer)
    {

        $item = $observer->getEvent()->getOrderItem();
        $quote = $observer->getEvent()->getItem()->getQuote();
        $item->setXeroRate('');
        $product = $observer->getEvent()->getItem()->getProduct();
        if ($product->getTaxClassId()) {
            $calculation = Mage::getSingleton('tax/calculation');
            $calculation->setCustomer($quote->getCustomer());
            $request = $calculation->getRateRequest($quote->getShippingAddress(), $quote->getBillingAddress());
            $request->setProductClassId($product->getTaxClassId());
            $rateIds = $calculation->getResource()->getRateIds($request);

            if (!empty($rateIds)) {
                $xeroRates = array();
                foreach ($rateIds as $rateId) {
                    $rate = Mage::getSingleton('tax/calculation_rate')->load($rateId);
                    if ($rate->getXeroRate()) {
                        $xeroRates[$rateId] = $rate->getXeroRate();
                    }
                }
                if (!empty($xeroRates)) {
                    $item->setXeroRate(implode(',', $xeroRates));
                }
                //Magento fix
                //tax percentage is sometimes not saved on quote items - for example on the parent bundle item
                //make sure the actual tax amount is within reasonable distance to the calculated tax rate
                //and save the percentage
                if ($item->getTaxPercent() < 0.00001 && $rate->getRate() && $item->getBaseTaxAmount()
                        && abs($item->getBaseRowTotal() * $rate->getRate() / 100 - $item->getBaseTaxAmount() < 0.02)
                ) {
                    Mage::log('Set missing tax percentage on ' . $item->getName() . ' to ' . $rate->getRate(), null, Fooman_Connect_Model_XeroOauth::CA_LOG_FILENAME);
                    $item->setTaxPercent($rate->getRate());
                }
            }
        }
    }

}
