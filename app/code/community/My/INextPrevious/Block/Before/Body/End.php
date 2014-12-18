<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    My
 * @package     My_INextPrevious
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Before Body End Block
 *
 * @category   My
 * @package    My_INextPrevious
 * @author     Theodore Doan <theodore.doan@gmail.com>
 */
class My_INextPrevious_Block_Before_Body_End extends Mage_Core_Block_Template
{
    const XML_PATH_LOOP_PRODUCT = 'inextprevious/general/loop_product';
    const XML_PATH_SHOW_IMAGE   = 'inextprevious/general/show_image';
    const XML_PATH_IMAGE_WIDTH  = 'inextprevious/general/image_width';
    const XML_PATH_IMAGE_HEIGHT = 'inextprevious/general/image_height';

    const CUSTOM_QUERY_NAME = 'my_next_previous_query';

    /**
     * Previous Product
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_previousProduct = null;
    /**
     * Next Product
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_nextProduct = null;
    /**
     * Current referer
     *
     * @var string
     */
    protected $_currentReferer = null;

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (Mage::registry('current_category') and Mage::registry('current_product')) {
            $_currentReferer    = parse_url(Mage::app()->getRequest()->getServer('HTTP_REFERER'));
            $_previousQuery     = 0;
            if (isset($_currentReferer['query'])) {
                parse_str($_currentReferer['query'], $output);
                if (isset($output[self::CUSTOM_QUERY_NAME])) {
                } else {
                    $_previousQuery = crc32($_currentReferer['query']);
                }
            }
            $_customQuery       = Mage::app()->getRequest()->get(self::CUSTOM_QUERY_NAME);
            if ($_customQuery) {
                $_previousQuery = $_customQuery;
            }
            $this->_currentReferer = $_previousQuery;

            $_categoryModel = Mage::registry('current_category');
            $_productModel  = Mage::registry('current_product');
            $_categoryId    = $_categoryModel->getId();
            $_cacheId       = 'MY_INEXTPREVIOUS_' . $_categoryId;
            $_cachedData    = Mage::app()->loadCache($_cacheId);
            if ($_cachedData) {
                $_cachedData = unserialize($_cachedData);
                if (isset($_cachedData[$_previousQuery]) and is_array($_cachedData[$_previousQuery])) {
                    foreach ($_cachedData[$_previousQuery] as $_productList) {
                        $_productIndex = array_search($_productModel->getId(), $_productList);
                        if ($_productIndex >= 0) {
                            if ($_productIndex == 0) {
                                $_loopProduct = Mage::getStoreConfig(self::XML_PATH_LOOP_PRODUCT);
                                if ($_loopProduct == 1) {
                                    $_previousProductId = $_productList[sizeof($_productList) - 1];
                                    $this->_previousProduct = Mage::getModel('catalog/product')->load($_previousProductId);
                                }
								if (isset($_productList[$_productIndex + 1])){ // MTU
                               		$_nextProductId = $_productList[$_productIndex + 1];
	                                $this->_nextProduct = Mage::getModel('catalog/product')->load($_nextProductId);
								}
                            } elseif ($_productIndex == sizeof($_productList) - 1) {
                                $_previousProductId = $_productList[$_productIndex - 1];
                                $this->_previousProduct = Mage::getModel('catalog/product')->load($_previousProductId);

                                $_loopProduct = Mage::getStoreConfig(self::XML_PATH_LOOP_PRODUCT);
                                if ($_loopProduct == 1) {
                                    $_nextProductId = $_productList[0];
                                    $this->_nextProduct = Mage::getModel('catalog/product')->load($_nextProductId);
                                }
                            } else {
                                $_previousProductId = $_productList[$_productIndex - 1];
                                $this->_previousProduct = Mage::getModel('catalog/product')->load($_previousProductId);

                                $_nextProductId = $_productList[$_productIndex + 1];
                                $this->_nextProduct = Mage::getModel('catalog/product')->load($_nextProductId);
                            }
                            break;
                        }
                    }
                }
            }
        } elseif (Mage::registry('current_category')) {
            $_categoryModel = Mage::registry('current_category');
            $_productListBlock = Mage::getSingleton('core/layout')->getBlockSingleton('catalog/product_list');
            if ($_productListBlock) {
                $_productList = array_keys($_productListBlock->getLoadedProductCollection()->getItems());
                $_productListToolbarBlock = $_productListBlock->getToolbarBlock();

                $_currentUri    = parse_url(Mage::app()->getRequest()->getRequestUri());
                $_currentPage   = $_productListToolbarBlock->getCurrentPage();
                $_currentQuery  = 0;
                if (isset($_currentUri['query'])) {
                    $_currentQuery = crc32($_currentUri['query']);
                }

                $_categoryId        = $_categoryModel->getId();
                $_cacheTag          = array('MY_INEXTPREVIOUS');
                $_cacheId           = 'MY_INEXTPREVIOUS_' . $_categoryId;
                $_cachedData        = Mage::app()->loadCache($_cacheId);
                if ($_cachedData) {
                    $_cachedData = unserialize($_cachedData);
                }
                if (!is_array($_cachedData)) {
                    $_cachedData = array();
                }
                if (!isset($_cachedData[$_currentQuery])) {
                    $_cachedData[$_currentQuery] = array();
                }
                $_cachedData[$_currentQuery][$_currentPage] = $_productList;

                Mage::app()->saveCache(serialize($_cachedData), $_cacheId, $_cacheTag);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get previous product
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _getPrevious()
    {
        return $this->_previousProduct;
    }

    /**
     * Get next product
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _getNext()
    {
        return $this->_nextProduct;
    }

    /**
     * Get previous product url
     *
     * @return string
     */
    protected function _getPreviousUrl()
    {
        if ($this->_previousProduct) {
            $useSid = Mage::app()->getUseSessionInUrl();

            $params = array();
            if (!$useSid) {
                $params['_nosid'] = true;
            }
            if ($this->_currentReferer) {
                $params[self::CUSTOM_QUERY_NAME] = $this->_currentReferer;
            }
            $_previousUrl = $this->_previousProduct->getUrlModel()->getProductUrl($this->_previousProduct);
            if (strpos($_previousUrl, self::CUSTOM_QUERY_NAME) === false) {
                if ($this->_currentReferer == '0') {
                } else {
                    if (strpos($_previousUrl, '?') === false) {
                        $_previousUrl .= '?' . self::CUSTOM_QUERY_NAME . '=' . $this->_currentReferer;
                    } else {
                        $_previousUrl .= '&' . self::CUSTOM_QUERY_NAME . '=' . $this->_currentReferer;
                    }
                }
            }
            return $_previousUrl;
        }
    }

    /**
     * Get next product url
     *
     * @return string
     */
    protected function _getNextUrl()
    {
        if ($this->_nextProduct) {
            $useSid = Mage::app()->getUseSessionInUrl();

            $params = array();
            if (!$useSid) {
                $params['_nosid'] = true;
            }
            if ($this->_currentReferer) {
                $params[self::CUSTOM_QUERY_NAME] = $this->_currentReferer;
            }
            $_nextUrl = $this->_nextProduct->getUrlModel()->getProductUrl($this->_nextProduct);
            if (strpos($_nextUrl, self::CUSTOM_QUERY_NAME) === false) {
                if ($this->_currentReferer == '0') {
                } else {
                    if (strpos($_nextUrl, '?') === false) {
                        $_nextUrl .= '?' . self::CUSTOM_QUERY_NAME . '=' . $this->_currentReferer;
                    } else {
                        $_nextUrl .= '&' . self::CUSTOM_QUERY_NAME . '=' . $this->_currentReferer;
                    }
                }
            }
            return $_nextUrl;
        }
    }

    /**
     * Show Image
     *
     * @return bool
     */
    protected function _showImage()
    {
        return (Mage::getStoreConfig(self::XML_PATH_SHOW_IMAGE) == '1' ? true : false);
    }

    /**
     * Image Width
     *
     * @return int
     */
    protected function _imageWidth()
    {
        return Mage::getStoreConfig(self::XML_PATH_IMAGE_WIDTH);
    }

    /**
     * Image Height
     *
     * @return int
     */
    protected function _imageHeight()
    {
        return Mage::getStoreConfig(self::XML_PATH_IMAGE_HEIGHT);
    }
}