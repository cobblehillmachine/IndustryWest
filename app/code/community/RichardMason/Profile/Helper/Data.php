<?php
/**
 * Mason Web Development
 *
 * @category    RichardMason
 * @package     RichardMason_Profile
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RichardMason_Profile_Helper_Data extends Mage_Core_Helper_Abstract
{
	
	public function getText($category_id, $text){
		switch ($text) {
			case "New":
				switch ($category_id) {
					case 0:
						return $this->__('Add a News Article');						
					case 1:
						return $this->__('Add a Testimonial');						
					case 2:
						return $this->__('Add a Press Release');						
					case 3:
						return $this->__('Add a Press Article');						
						
				}
				break;
			case "_headerText":
				switch ($category_id) {
					case 0:
						return $this->__('News Manager');						
					case 1:
						return $this->__('Testimonials Manager');						
					case 2:
						return $this->__('Press Releases Manager');						
					case 3:
						return $this->__('Press Articles Manager');						
						
				}
				break;
			case "content_heading":
				switch ($category_id) {
					case 0:
						return $this->__('Heading');						
					case 1:
						return $this->__('Name');						
					case 2:
						return $this->__('Title');						
					case 3:
						return $this->__('Title');						
						
				}
				break;
			case "content":
				switch ($category_id) {
					case 0:
						return $this->__('Content');						
					case 1:
						return $this->__('Quote');						
					case 2:
						return $this->__('Quote');						
					case 3:
						return $this->__('Quote');						
						
				}
				break;
			case "thumbnail":
				switch ($category_id) {
					case 0:
						return $this->__('Thumbnail (Max. 90x90px)');						
					case 1:
					case 2:
					case 3:
						return $this->__('Thumbnail (Max. 100x130px)');						
						
				}
				break;
		}
	}
}