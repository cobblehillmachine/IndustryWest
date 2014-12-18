<?php

class Fooman_PdfCustomiser_Model_Updates extends Mage_AdminNotification_Model_Feed
{
    const FEED_URL = 'store.fooman.co.nz/news/';

    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://')
            . self::FEED_URL;
        }
        return $this->_feedUrl;
    }

    public function getLastUpdate()
    {
        return Mage::app()->loadCache('fooman_notifications_lastcheck');
    }

    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'fooman_notifications_lastcheck');
        return $this;
    }
}