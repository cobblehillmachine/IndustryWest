<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS `{$this->getTable('profile/profile')}`;
CREATE TABLE {$this->getTable('profile/profile')} (
  `profile_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `thumbnail` varchar(255) NOT NULL,
  `thumbnail_position` int(11) NOT NULL,
  `picture` varchar(255) NOT NULL,
  `picturetwo` varchar(255) NOT NULL,
  `picturethree` varchar(255) NOT NULL,
  `picturefour` varchar(255) NOT NULL,
  `picturefive` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `content_heading` varchar(255) NOT NULL DEFAULT '',
  `content` mediumtext,
  `meta_keywords` text NOT NULL,
  `meta_description` text NOT NULL,
  `creation_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`profile_id`),
  KEY `identifier` (`content_heading`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('profile/profile_store')}`;
CREATE TABLE {$this->getTable('profile/profile_store')} (
  `profile_id` smallint(6) NOT NULL,
  `store_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`profile_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='CMS Profiles to Stores';


$installer->endSetup(); 