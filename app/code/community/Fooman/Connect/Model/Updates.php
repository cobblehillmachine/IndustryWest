<?php

class Fooman_Connect_Model_Updates extends Mage_AdminNotification_Model_Feed {
    const RSS_UPDATES_URL = 'store.fooman.co.nz/news/cat/fooman-connect/updates';
    const XML_GET_CONNECT_UPDATES_PATH = 'foomancommon/notifications/enableconnectupdates';

    public function getFeedUrl() {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://')
                    . self::RSS_UPDATES_URL;
        }
        return $this->_feedUrl;
    }

    public function getLastUpdate() {
        return Mage::app()->loadCache('foomanconnect_notifications_lastcheck');
    }

    public function setLastUpdate() {
        Mage::app()->saveCache(time(), 'foomanconnect_notifications_lastcheck');
        return $this;
    }

    public function checkUpdate() {
        if(Mage::getStoreConfigFlag(self::XML_GET_CONNECT_UPDATES_PATH)) {
            Mage::log('Looking for updates - Fooman Connect');
            parent::checkUpdate();
        }
    }

}