<?php
/**
* aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE-COMMUNITY.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento COMMUNITY edition
 * aheadWorks does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * aheadWorks does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Ajaxcartpro
 * @version    2.4.1
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE-COMMUNITY.txt
 */

class AW_Ajaxcartpro_Helper_Data_Mag18 extends AW_Ajaxcartpro_Helper_Data {

    const EE_PLATFORM = 100;
    const PE_PLATFORM = 10;
    const CE_PLATFORM = 0;
    const ENTERPRISE_DETECT_COMPANY = 'Enterprise';
    const ENTERPRISE_DETECT_EXTENSION = 'Enterprise';
    const ENTERPRISE_DESIGN_NAME = "enterprise";
    const PROFESSIONAL_DESIGN_NAME = "pro";

    protected static $_platform = -1;
    /**
     * Checks which edition is used
     * @return int
     */
     private function getPlatform()
    {
        if (self::$_platform == -1) {
            $pathToClaim = BP . DS . "app" . DS . "etc" . DS . "modules" . DS . self::ENTERPRISE_DETECT_COMPANY . "_" . self::ENTERPRISE_DETECT_EXTENSION .  ".xml";
            $pathToEEConfig = BP . DS . "app" . DS . "code" . DS . "core" . DS . self::ENTERPRISE_DETECT_COMPANY . DS . self::ENTERPRISE_DETECT_EXTENSION . DS . "etc" . DS . "config.xml";
            $isCommunity = !file_exists($pathToClaim) || !file_exists($pathToEEConfig);
            if ($isCommunity) {
                 self::$_platform = self::CE_PLATFORM;
            } else {
                $_xml = @simplexml_load_file($pathToEEConfig,'SimpleXMLElement', LIBXML_NOCDATA);
                if(!$_xml===FALSE) {
                    $package = (string)$_xml->default->design->package->name;
                    $theme = (string)$_xml->install->design->theme->default;
                    $skin = (string)$_xml->stores->admin->design->theme->skin;
                    $isProffessional = ($package == self::PROFESSIONAL_DESIGN_NAME) && ($theme == self::PROFESSIONAL_DESIGN_NAME) && ($skin == self::PROFESSIONAL_DESIGN_NAME);
                    if ($isProffessional) {
                        self::$_platform = self::PE_PLATFORM;
                        return self::$_platform;
                    }
                }
                self::$_platform = self::EE_PLATFORM;
            }
        }
        return self::$_platform;
    }
    /**
     * Return small cart rendered HTML
     * @return string
     */
    public function renderCart() {
        $platform = self::getPlatform();
        $layout =  Mage::getSingleton('core/layout');
        $sidebar = $layout
                ->createBlock('checkout/cart_sidebar')
                ->addItemRender('simple', 'checkout/cart_item_renderer', 'checkout/cart/sidebar/default.phtml')
                ->addItemRender('configurable', 'checkout/cart_item_renderer_configurable', 'checkout/cart/sidebar/default.phtml')
                ->addItemRender('grouped', 'checkout/cart_item_renderer_grouped', 'checkout/cart/sidebar/default.phtml')
                ->addItemRender('bundle', 'bundle/checkout_cart_item_renderer', 'checkout/cart/sidebar/default.phtml');
        if($platform==10)
        {
            if (Mage::helper('ajaxcartpro')->extensionEnabled('AW_Points'))
        {
            $pointsInfoBlock = $layout
                    ->createBlock('core/template')
                    ->setTemplate('aw_points/infopagelink.phtml');
            $pointsBlock = $layout
                    ->createBlock('points/checkout_cart_points')
                    ->setTemplate('aw_points/checkout/cart/sidebar/points.phtml')
                    ->setChild('infopage.link', $pointsInfoBlock);
            $sidebar
                ->setChild('checkout.cart.sidebar.points', $pointsBlock)
                ->setTemplate('aw_points/checkout/cart/sidebar.phtml');
        }
        else
        {
            $sidebar->setTemplate('checkout/cart/sidebar.phtml');
        }
        }
            else
                $sidebar->setTemplate('checkout/cart/cartheader.phtml');
              return  $sidebar->renderView();
    }

    /**
     * Return top link with cart items
     * @return string
     */
    public function renderTopCartLinkTitle() {
        $count = Mage::helper('checkout/cart')->getSummaryCount();
        return Mage::helper('checkout')->__('My Cart <span>(%s)</span>', $count);
    }

    public function addAdditionalBlocks($cart)
    {
        $L = Mage::getSingleton('core/layout');
        $giftcart = $L
                ->createBlock('enterprise_giftcardaccount/checkout_cart_giftcardaccount')
                ->setTemplate('giftcardaccount/cart/block.phtml');
        $cart->setChild('giftcards', $giftcart);
    }
}
