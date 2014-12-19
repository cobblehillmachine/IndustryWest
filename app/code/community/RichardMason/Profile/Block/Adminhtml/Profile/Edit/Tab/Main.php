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
class RichardMason_Profile_Block_Adminhtml_Profile_Edit_Tab_Main
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	
    /**
     * Load Wysiwyg on demand and Prepare layout
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
    }
	
    protected function _prepareForm()
    {
        /* @var $model Mage_Cms_Model_Page */
        $model = Mage::registry('profile_profile');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }


        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('profile_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('profile')->__('Content')));
        
        //profile_id
        if ($model->getProfileId()) {
            $fieldset->addField('profile_id', 'hidden', array(
                'name' => 'profile_id',
            ));
        }
        //category_id
		$fieldset->addField('category_id', 'hidden', array(
			'name' => 'category_id',
		));
		//head of content
        $fieldset->addField('content_heading', 'text', array(
            'name'      => 'content_heading',
            'label'     =>  Mage::helper('profile')->getText($model->getData("category_id"),'content_heading'),
            'title'     =>  Mage::helper('profile')->getText($model->getData("category_id"),'content_heading'),
            'disabled'  => $isElementDisabled
        ));
        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );
        $fieldset->addField('creation_time', 'date', array(
            'name'      => 'creation_time',
            'label'     => Mage::helper('profile')->__('Date'),
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'format'    => $dateFormatIso
        ));
        


        if($model->getData("category_id") != RichardMason_Profile_Model_Profile::CATEGORY_TESTIMONIALS) {
	      $fieldset->addField('thumbnail', 'image', array(
	          'label'     => Mage::helper('profile')->__('Thumbnail (Width 140px)'),
	          'required'  => false,
	          'name'      => 'thumbnail',
		  ));
		  
	      $fieldset->addField('thumbnail_position', 'select', array(
	            'label'     => Mage::helper('profile')->__('Thumbnail Position'),
	            'title'     => Mage::helper('profile')->__('Thumbnail Position'),
	            'name'      => 'thumbnail_position',
	            'required'  => true,
	            'options'   => $model->getAvailableThumbnailPositions()
	        ));
        }
	  
	  if($model->getData("category_id") == RichardMason_Profile_Model_Profile::CATEGORY_ARTICLES) {
	  		$fieldset->addField('file', 'image', array(
	          'label'     => Mage::helper('profile')->__('PDF File'),
	          'required'  => false,
	          'name'      => 'file',
		  ));
	  
	        $fieldset->addField('picture', 'image', array(
	          'label'     => Mage::helper('profile')->__('Image - Page 1 (Width 1000px)'),
	          'required'  => true,
	          'name'      => 'picture',
		  ));
		  
		  $fieldset->addField('picturetwo', 'image', array(
	          'label'     => Mage::helper('profile')->__('Image - Page 2 (Width 1000px)'),
	          'required'  => false,
	          'name'      => 'picturetwo',
		  ));
		  
		  $fieldset->addField('picturethree', 'image', array(
	          'label'     => Mage::helper('profile')->__('Image - Page 3 (Width 1000px)'),
	          'required'  => false,
	          'name'      => 'picturethree',
		  ));
		  
		  $fieldset->addField('picturefour', 'image', array(
	          'label'     => Mage::helper('profile')->__('Image - Page 4 (Width 1000px)'),
	          'required'  => false,
	          'name'      => 'picturefour',
		  ));
		  
		  $fieldset->addField('picturefive', 'image', array(
	          'label'     => Mage::helper('profile')->__('Image - Page 5 (Width 1000px)'),
	          'required'  => false,
	          'name'      => 'picturefive',
		  ));
	  }

	  if($model->getData("category_id") == RichardMason_Profile_Model_Profile::CATEGORY_RELEASES) {
	  $fieldset->addField('file', 'image', array(
	          'label'     => Mage::helper('profile')->__('PDF File'),
	          'required'  => false,
	          'name'      => 'file',
		  ));
	  }

        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(
            array('tab_id' => $this->getTabId())
        );
        //make Wysiwyg Editor integrate in the form
        $wysiwygConfig["files_browser_window_url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg_images/index');
        $wysiwygConfig["directives_url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg/directive');
        $wysiwygConfig["directives_url_quoted"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg/directive');
        $wysiwygConfig["widget_window_url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/widget/index');
        $plugins = $wysiwygConfig->getData("plugins");
        $plugins[0]["options"]["url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/system_variable/wysiwygPlugin');
        $plugins[0]["options"]["onclick"]["subject"] = "MagentovariablePlugin.loadChooser('".Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/system_variable/wysiwygPlugin')."', '{{html_id}}');";
        $plugins = $wysiwygConfig->setData("plugins",$plugins);
        $contentField = $fieldset->addField('content', 'editor', array(
            'name'      => 'content',
            'style'     => 'height:20em; width:50em;',
            'required'  => false,
            'disabled'  => $isElementDisabled,
            'config'    => $wysiwygConfig
        ));
        // Setting custom renderer for content field to remove label column
        $renderer = $this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset_element')
                    ->setTemplate('cms/page/edit/form/renderer/content.phtml');
        $contentField->setRenderer($renderer);

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'stores[]',
                'label'     => Mage::helper('cms')->__('Store View'),
                'title'     => Mage::helper('cms')->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'disabled'  => $isElementDisabled
            ));
        }
        else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'      => 'stores[]',
                'value'     => Mage::app()->getStore(true)->getId()
            ));
            $model->setStoreId(Mage::app()->getStore(true)->getId());
        }
        
        $fieldset->addField('is_active', 'select', array(
            'label'     => Mage::helper('profile')->__('Status'),
            'title'     => Mage::helper('profile')->__('Status'),
            'name'      => 'is_active',
            'required'  => true,
            'options'   => $model->getAvailableStatuses(),
            'disabled'  => $isElementDisabled,
        ));
        if (!$model->getId()) {
            $model->setData('is_active', $isElementDisabled ? '0' : '1');
        }

        Mage::dispatchEvent('adminhtml_profile_edit_tab_main_prepare_form', array('form' => $form));
        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('profile')->__('Content');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('profile')->__('Content');
    }

    /**
     * Returns status flag about this tab can be shown or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
		return true;
//        return Mage::getSingleton('admin/session')->isAllowed('profile/' . $action);
    }
}
