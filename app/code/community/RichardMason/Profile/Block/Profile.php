<?php
/**
 * Mason Web Development
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * Part of the code of this file was obtained from:
 * -category    Mage
 * -package     Mage_Adminhtml
 * -copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * -license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @category    RichardMason
 * @package     RichardMason_Profile
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RichardMason_Profile_Block_Profile extends Mage_Core_Block_Template
{
	public function _prepareLayout(){
		return parent::_prepareLayout();
    }
    
    public function getNews(){
		return $this->getProfiles(RichardMason_Profile_Model_Profile::CATEGORY_NEWS);
    }
    
    public function getTestimonials(){
		return $this->getProfiles(RichardMason_Profile_Model_Profile::CATEGORY_TESTIMONIALS);
	}
	
    public function getPressarticles(){
		return $this->getProfiles(RichardMason_Profile_Model_Profile::CATEGORY_ARTICLES);
    }
    
    public function getPressreleases(){
		return $this->getProfiles(RichardMason_Profile_Model_Profile::CATEGORY_RELEASES);
    }

    /*
     * Return a collection of profiles. 
     * @param category_id is the category of profile.
     * @return Collection of Profile Model
     */
	public function getProfiles($category_id) {
		$profiles = Mage::getModel('profile/profile')->getCollection()
			->addStoreFilter(Mage::app()->getStore()->getId());
		$profiles->addFieldToFilter('category_id', $category_id);
		$profiles->setOrder("creation_time", "DESC");
		return $profiles;
	}
    
	/*
	 * Return a specific profile.
	 * @param GET or POST "id"
	 * @return Profile model class
	 */
    public function getProfile(){
    	if($profile_id = $this->getRequest()->getParam('id')){
	  		if($profile_id != null && $profile_id != ''){
				$profile = Mage::getModel('profile/profile')->load($profile_id);
			} else {
				$profile = null;
			}	
			$this->setData('profile', $profile);
		}
		return $this->getData('profile');
	}
	
}