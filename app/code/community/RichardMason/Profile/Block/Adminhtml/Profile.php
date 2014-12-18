<?php
/**
 * Mason Web Development
 *
 * @category    RichardMason
 * @package     RichardMason_Profile
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RichardMason_Profile_Block_Adminhtml_Profile extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_profile';
    $this->_blockGroup = 'profile';
    
    $this->_headerText = Mage::helper('profile')->getText(Mage::registry('profile_category_id'), '_headerText');
    parent::__construct();
    parent::_addButton('add', array(
		'label'     => Mage::helper('profile')->getText(Mage::registry('profile_category_id'), 'New'),
		'onclick'   => 'setLocation(\''.$this->getUrl('*/*/edit', array('category_id' => Mage::registry('profile_category_id'))).'\')',
        'class'     => 'add',
    ));    
  }
}