<?php
/**
 * Mason Web Development
 *
 * @category    RichardMason
 * @package     RichardMason_Profile
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RichardMason_Profile_PressreleasesController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
		$this->loadLayout();     
		$this->renderLayout();
    }
    
    public function readAction()
    {
		$this->loadLayout();     
		$block=Mage::getBlockSingleton('profile/profile');
		//load profile
    	$profile = $block->getProfile();
    	if($profile) {
		 	$this->getLayout()->getBlock('head')->setTitle($profile->getData("content_heading"))
				->setDescription($profile->getData("meta_description"))
				->setKeywords($profile->getData("meta_keywords"));
    	}
		$this->renderLayout();
    }    
    
}