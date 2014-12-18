<?php
/**
 * Mason Web Development
 *
 * @category    RichardMason
 * @package     RichardMason_Profile
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RichardMason_Profile_Model_Profile extends Mage_Core_Model_Abstract
{
    /**
     * Profile's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    
    /**
     * Thumbnail's positions
     */
    const THUMBNAIL_LEFT = 0;
    const THUMBNAIL_RIGHT = 1;
    
    /**
	 * Profile's Categories
	 */
    const CATEGORY_NEWS = 0;
    const CATEGORY_TESTIMONIALS = 1;
    const CATEGORY_RELEASES = 2;
    const CATEGORY_ARTICLES = 3;

    public function _construct()
    {
        parent::_construct();
        $this->_init('profile/profile');
    }
    
    
    /**
     * Prepare profile thumbnail positions.
     *
     * @return array
     */
    public function getAvailableThumbnailPositions()
    {
        $statuses = new Varien_Object(array(
            self::THUMBNAIL_LEFT => Mage::helper('profile')->__('Left'),
            self::THUMBNAIL_RIGHT => Mage::helper('profile')->__('Right'),
        ));

        Mage::dispatchEvent('profile_get_available_statuses', array('statuses' => $statuses));

        return $statuses->getData();
    }
    
    
    /**
     * Prepare profile's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        $statuses = new Varien_Object(array(
            self::STATUS_ENABLED => Mage::helper('profile')->__('Enabled'),
            self::STATUS_DISABLED => Mage::helper('profile')->__('Disabled'),
        ));

        Mage::dispatchEvent('profile_get_available_statuses', array('statuses' => $statuses));

        return $statuses->getData();
    }
    
    /**
     * Get the align position of the Thumnail picture
     */
    public function getThumbnailAlign()
    {
    	switch($this->getData("thumbnail_position")){
    		case self::THUMBNAIL_LEFT:
    			return "left";
    		case self::THUMBNAIL_RIGHT:
    			return "right";
    	}
    	return "";	
    }

    /**
     * Return the URL of the Thumbnail new image resized to the params.
     * 
     * @param $with With of the resized image
     * @param $height Height of the resized image (Default: null)
     * @return the Url of the new resized image.
     */
    public function getThumbnailResize($with, $height = null) {
		// actual path of image
		$imageUrl = Mage::getBaseDir('media').DS.$this->getData("thumbnail");
		
		// path of the resized image to be saved
		// here, the resized image is saved in media/resized folder
		$imageResized = Mage::getBaseDir('media').DS."resized_".$this->getData("thumbnail"); 
		
		// resize image only if the image file exists and the resized image file doesn't exist
		// the image is resized proportionally with the width/height 135px
		if (!file_exists($imageResized)&&file_exists($imageUrl)) {
			$imageObj = new Varien_Image($imageUrl);
			$imageObj->constrainOnly(TRUE);
			$imageObj->keepAspectRatio(TRUE);
			$imageObj->keepFrame(FALSE);
			$imageObj->resize($with, $height);
			$imageObj->save($imageResized);
		}
		return (Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "media/" . "resized_".$this->getData("thumbnail"));
    }
    
    /**
     * Return the URL of the Thumbnail new image resized to the params.
     * 
     * @param $with With of the resized image
     * @param $height Height of the resized image (Default: null)
     * @return the Url of the new resized image.
     */
    public function getPhotoResize($name, $with, $height = null) {
		// actual path of image
		$imageUrl = Mage::getBaseDir('media').DS.$this->getData($name);
		
		// path of the resized image to be saved
		// here, the resized image is saved in media/resized folder
		$imageResized = Mage::getBaseDir('media').DS."resized_".$this->getData($name); 
		
		// resize image only if the image file exists and the resized image file doesn't exist
		// the image is resized proportionally with the width/height 135px
		if (!file_exists($imageResized)&&file_exists($imageUrl)) {
			$imageObj = new Varien_Image($imageUrl);
			$imageObj->constrainOnly(TRUE);
			$imageObj->keepAspectRatio(TRUE);
			$imageObj->keepFrame(FALSE);
			$imageObj->quality(90);			
			$imageObj->resize($with, $height);
			$imageObj->save($imageResized);
		}
		return (Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "media/" . "resized_".$this->getData($name));
    }
    
    /**
     * Return the Url key for the category
     * 
     * @param category_id
     * @return string
     */
    public function getCategoryUrl($category_id)
    {
    	$url="";

    	switch((int)$category_id){
    		case self::CATEGORY_NEWS:
    			$url = "news";
    			break;
    		case self::CATEGORY_TESTIMONIALS:
    			$url = "testimonials";
    			break;
    		case self::CATEGORY_RELEASES:
    			$url = "pressreleases";
    			break;
    		case self::CATEGORY_ARTICLES:
    			$url = "pressarticles";
    			break;
    	}
    	
    	return($url);
    }
    
    /**
     * Check if page identifier exist for specific store
     * return page id if page exists
     *
     * @param int $profile_id
     * @param int $storeId
     * @return int
     */
    public function checkIdentifier($profile_id, $storeId)
    {
        return $this->_getResource()->checkIdentifier($profile_id, $storeId);
    }
    
    
	private function html_cut_text($text,$limit)
	{
		$max_code=8; // max for special code like &permil; &aacute; ...
		
		if(($limit<>0)&&(strlen($text)>$limit))
		{
			$pos=strpos(substr($text,0,$limit+$max_code),';'); //find ; 
			if($pos) // cut when pass ;
				$text=substr($text,0,$pos+1);
			else //cut at limit
				$text=substr($text,0,$limit); 
			return($text."...");
		}
		else
			return($text); // return
	}
	
	/**
	 * Remove HTML tags, including invisible text such as style and
	 * script code, and embedded objects.  Add line breaks around
	 * block-level tags to prevent word joining after tag removal.
	 */
	public function strip_html_tags( $text )
	{
		$text = preg_replace(
			array(
			  // Remove invisible content
				'@<head[^>]*?>.*?</head>@siu',
				'@<style[^>]*?>.*?</style>@siu',
				'@<script[^>]*?.*?</script>@siu',
				'@<object[^>]*?.*?</object>@siu',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<applet[^>]*?.*?</applet>@siu',
				'@<noframes[^>]*?.*?</noframes>@siu',
				'@<noscript[^>]*?.*?</noscript>@siu',
				'@<noembed[^>]*?.*?</noembed>@siu',
			  // Add line breaks before and after blocks
				'@</?((address)|(blockquote)|(center)|(del))@iu',
				'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
				'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
				'@</?((table)|(th)|(td)|(caption))@iu',
				'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
				'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
				'@</?((frameset)|(frame)|(iframe))@iu',
			),
			array(
				' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
				"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
				"\n\$0", "\n\$0",
			),
			$text );
		return strip_tags( $text );
	}
	
    public function getContent($limit = 0)
    {
    	//process page
		$helper = Mage::helper('cms');
        $processor = $helper->getPageTemplateProcessor();
        $html = $processor->filter($this->getData("content"));
    	if($limit)
    		return $this->html_cut_text($this->strip_html_tags($html), $limit);
    	else
	   		return ($html);
    }
    
}