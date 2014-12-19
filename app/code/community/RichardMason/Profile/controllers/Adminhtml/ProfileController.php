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
class RichardMason_Profile_Adminhtml_ProfileController extends Mage_Adminhtml_Controller_action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('profile/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Profiles Manager'), Mage::helper('adminhtml')->__('Profile Manager'));
		
		return $this;
	}

	/*
	 * News Index
	 */
	public function newsAction(){
		Mage::register('profile_category_id', RichardMason_Profile_Model_Profile::CATEGORY_NEWS);
		$this->_initAction()
			->renderLayout();
	}
	/*
	 * Testimonials Index
	 */
	public function testimonialsAction(){
		Mage::register('profile_category_id', RichardMason_Profile_Model_Profile::CATEGORY_TESTIMONIALS);
		$this->_initAction()
			->renderLayout();
	}
	/*
	 * Press Releases Index
	 */
	public function pressreleasesAction(){
		Mage::register('profile_category_id', RichardMason_Profile_Model_Profile::CATEGORY_RELEASES);
		$this->_initAction()
			->renderLayout();
	}	
	/*
	 * Press Articles Index
	 */
	public function pressarticlesAction(){
		Mage::register('profile_category_id', RichardMason_Profile_Model_Profile::CATEGORY_ARTICLES);
		$this->_initAction()
			->renderLayout();
	}

	/**
     * Edit CMS page
     */
    public function editAction()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('profile_id');
        $model = Mage::getModel('profile/profile');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (! $model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('profile')->__('This profile no longer exists'));
                $this->_redirect('*/*/');
                return;
            }
        }

        // 3. Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (! empty($data)) {
            $model->setData($data);
        }
        if($this->getRequest()->getParam('category_id'))
        	$model->setData("category_id", $this->getRequest()->getParam('category_id'));

        // 4. Register model to use later in blocks
        Mage::register('profile_profile', $model);

        // 5. Build edit form
        $this->_initAction()
             ->_addBreadcrumb(Mage::helper('profile')->__('CMS'), Mage::helper('profile')->__('CMS'))
            ->_addContent($this->getLayout()->createBlock('profile/adminhtml_profile_edit'))
            ->_addLeft($this->getLayout()->createBlock('profile/adminhtml_profile_edit_tabs'));

		$this->renderLayout();
    }
	
   /**
     * Save action
     */
    public function saveAction()
    {
        // check if data sent
        if ($data = $this->getRequest()->getPost()) {
        	//thumbnail file
        	if(isset($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['name'] != '') {
				try {	
					/* Starting upload */	
					$uploader = new Varien_File_Uploader('thumbnail');
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['thumbnail']['name'] );
				} catch (Exception $e) {
    	            $this->_getSession()->addException($e, Mage::helper('profile')->__('Error uploading image. Please try again later.'));
		        }
	  			$data['thumbnail'] = $_FILES['thumbnail']['name'];
			}
			else
			{
				if(isset($data['thumbnail']['delete']) && $data['thumbnail']['delete'] == 1)
					$data["thumbnail"]="";
				else
					unset($data["thumbnail"]);
			}
			
        	if(isset($_FILES['picturetwo']['name']) && $_FILES['picturetwo']['name'] != '') {
				try {	

					/* Starting upload */	
					$uploader = new Varien_File_Uploader('picturetwo');
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['picturetwo']['name'] );
				} catch (Exception $e) {
    	            $this->_getSession()->addException($e, Mage::helper('profile')->__('Error uploading image. Please try again later.'));

		        }
	  			$data['picturetwo'] = $_FILES['picturetwo']['name'];
	  			
			}
			else
			{
				if(isset($data['picturetwo']['delete']) && $data['picturetwo']['delete'] == 1)
					$data["picturetwo"]="";
				else
					unset($data["picturetwo"]);
			}
			
        if(isset($_FILES['picturethree']['name']) && $_FILES['picturethree']['name'] != '') {
				try {	
					/* Starting upload */	
					$uploader = new Varien_File_Uploader('picturethree');
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['picturethree']['name'] );
				} catch (Exception $e) {
    	            $this->_getSession()->addException($e, Mage::helper('profile')->__('Error uploading image. Please try again later.'));
		        }
	  			$data['picturethree'] = $_FILES['picturethree']['name'];
			}
			else
			{
				if(isset($data['picturethree']['delete']) && $data['picturethree']['delete'] == 1)
					$data["picturethree"]="";
				else
					unset($data["picturethree"]);
			}
			
        if(isset($_FILES['picturefour']['name']) && $_FILES['picturefour']['name'] != '') {
				try {	
					/* Starting upload */	
					$uploader = new Varien_File_Uploader('picturefour');
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['picturefour']['name'] );
				} catch (Exception $e) {
    	            $this->_getSession()->addException($e, Mage::helper('profile')->__('Error uploading image. Please try again later.'));
		        }
	  			$data['picturefour'] = $_FILES['picturefour']['name'];
			}
			else
			{
				if(isset($data['picturefour']['delete']) && $data['picturefour']['delete'] == 1)
					$data["picturefour"]="";
				else
					unset($data["picturefour"]);
			}
			
        if(isset($_FILES['picturefive']['name']) && $_FILES['picturefive']['name'] != '') {
				try {	
					/* Starting upload */	
					$uploader = new Varien_File_Uploader('picturefive');
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['picturefive']['name'] );
				} catch (Exception $e) {
    	            $this->_getSession()->addException($e, Mage::helper('profile')->__('Error uploading image. Please try again later.'));
		        }
	  			$data['picturefive'] = $_FILES['picturefive']['name'];
			}
			else
			{
				if(isset($data['picturefive']['delete']) && $data['picturefive']['delete'] == 1)
					$data["picturefive"]="";
				else
					unset($data["picturefive"]);
			}
			
			//file pdf,...
        	if(isset($_FILES['file']['name']) && $_FILES['file']['name'] != '') {
				try {	
					$uploader = new Varien_File_Uploader('file');
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('pdf'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['file']['name'] );
				} catch (Exception $e) {
    	            $this->_getSession()->addException($e, Mage::helper('profile')->__('Error uploading file. Please try again later.'));
		        }
	  			$data['file'] = $_FILES['file']['name'];
			}
			else
			{
				if(isset($data['file']['delete']) && $data['file']['delete'] == 1)
					$data["file"]="";
				else
					unset($data["file"]);
			}
			//picture file
			
        	if(isset($_FILES['picture']['name']) && $_FILES['picture']['name'] != '') {
				try {	
					/* Starting upload */	
					$uploader = new Varien_File_Uploader('picture');
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS ;
					$uploader->save($path, $_FILES['picture']['name'] );
				} catch (Exception $e) {
    	            $this->_getSession()->addException($e, Mage::helper('profile')->__('Error uploading image. Please try again later.'));
		        }
	  			$data['picture'] = $_FILES['picture']['name'];
			}
			else
			{
				if(isset($data['picture']['delete']) && $data['picture']['delete'] == 1)
					$data["picture"]="";
				else
					unset($data["picture"]);
			}

			
            //init model and set data
            $model = Mage::getModel('profile/profile');

            if ($id = $this->getRequest()->getParam('profile_id')) {
                $model->load($id);
            }
            

    
	  			
            $model->setData($data);
              // force new pictures to set, not sure why this is necessary MTU
           // $model->setData("picturetwo", $data["picturetwo"]);
          //  $model->setData("picturethree", $data["picturethree"]);
           // $model->setData("picturefour", $data["picturefour"]);
           // $model->setData("picturefive", $data["picturefive"]);



            $url = $model->getCategoryUrl($model->getData("category_id"));
            
            Mage::dispatchEvent('profile_prepare_save', array('profile' => $model, 'request' => $this->getRequest()));

            // try to save it
            try {
                // save the data
                $model->save();

                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('profile')->__('Profile was successfully saved'));
                // clear previously saved data from session
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('profile_id' => $model->getId()));
                    return;
                }
                // go to grid
                $this->_redirect('*/*/'.$url);
                return;

            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addException($e, Mage::helper('profile')->__('Error while saving. Please try again later.'.$e));
            }
            
            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', array('profile_id' => $this->getRequest()->getParam('profile_id')));
            return;
        }
        $this->_redirect('*/*/'.$url);
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        // check if we know what should be deleted
        if ($id = $this->getRequest()->getParam('profile_id')) {
            $title = "";
            try {
                // init model and delete
                $model = Mage::getModel('profile/profile');
                $model->load($id);
                $title = $model->getTitle();
                $model->delete();
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('profile')->__('Profile was successfully deleted'));
                // go to grid
                Mage::dispatchEvent('adminhtml_cmspage_on_delete', array('title' => $title, 'status' => 'success'));
                // go to grid
                $url = $model->getCategoryUrl($model->getData("category_id"));
                $this->_redirect('*/*/'.$url);
                return;

            } catch (Exception $e) {
                Mage::dispatchEvent('adminhtml_profile_on_delete', array('title' => $title, 'status' => 'fail'));
                // display error message
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                // go back to edit form
                $this->_redirect('*/*/edit', array('profile_id' => $id));
                return;
            }
        }
        // display error message
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('profile')->__('Unable to find a profile to delete'));
        // go to grid
        $this->_redirect('*/*/');
    }
    
    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('creation_time'));
        return $data;
    }

}