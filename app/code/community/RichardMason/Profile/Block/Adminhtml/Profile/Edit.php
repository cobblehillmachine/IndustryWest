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
class RichardMason_Profile_Block_Adminhtml_Profile_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'profile_id';
        $this->_blockGroup = 'profile';
        $this->_controller = 'adminhtml_profile';

        parent::__construct();

        $url=Mage::registry('profile_profile')->getCategoryUrl(Mage::registry('profile_profile')->getData("category_id"));
        parent::addButton('back', array(
            'label'     => Mage::helper('adminhtml')->__('Back'),
            'onclick'   => 'setLocation(\'' . $this->getUrl("*/*/".$url."/") . '\')',
            'class'     => 'back',
        ), -1);

        if ($this->_isAllowedAction('save')) {
            $this->_updateButton('save', 'label', Mage::helper('profile')->__('Save'));
            $this->_addButton('saveandcontinue', array(
                'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
                'onclick'   => 'saveAndContinueEdit()',
                'class'     => 'save',
            ), -100);
        } else {
            $this->_removeButton('save');
        }

        if ($this->_isAllowedAction('delete')) {
            $this->_updateButton('delete', 'label', Mage::helper('profile')->__('Delete'));
        } else {
            $this->_removeButton('delete');
        }

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('profile_profile')->getId()) {
            return Mage::helper('profile')->__("Edit '%s'", $this->htmlEscape(Mage::registry('profile_profile')->getData("content_heading")));
        }
        else {
            return Mage::helper('profile')->getText(Mage::registry('profile_profile')->getData("category_id"), 'New');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
		return true; // MTU
      /*   return Mage::getSingleton('admin/session')->isAllowed('profile/' . $action); */ // EDIT MTU
    }
}