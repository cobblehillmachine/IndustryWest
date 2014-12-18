<?php
/**
 * Product in category grid
 *
 * @category   Magedev
 * @package    Magedev_Productposition
 */
  
class Magedev_Productposition_Block_Catalog_Category_Tab_Enahancedproducts extends Mage_Adminhtml_Block_Widget_Grid
{
	protected $_pagerVisibility = false;
	
    public function __construct()
    {

        parent::__construct();
        $this->setId('catalog_category_enahancedproducts');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
		$this->setPagerVisibility(true);
		$this->setFilterVisibility(false);

		if ($this->getRequest()->getParam('id', 0)) {
			$this->setSortableRows(true);
		}    
		
		$magentoVersion =  Mage::getVersion();		
		if (preg_match("/\b1.3\b/i", $magentoVersion)) {
		$this->setTemplate('productposition/widget/grid1.3.phtml');
		
		}else{
			$this->setTemplate('productposition/widget/grid.phtml');
		}	
		
	}

    public function getCategory()
    {
        return Mage::registry('category');
    }

    protected function _addColumnFilterToCollection($column)
    {
	
        // Set custom filter for in category flag
        if ($column->getId() == 'in_category') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
            }
            elseif(!empty($productIds)) {
                $this->getCollection()->addFieldToFilter('entity_id', array('nin'=>$productIds));
            }
        }
        else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    protected function _prepareCollection()
    {
         if ($this->getCategory()->getId()) {
            $this->setDefaultFilter(array('in_category'=>1));
        }
	
		$store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
			->setPageSize(1000)->setCurPage(1)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('price')
			->addAttributeToSelect('type_id')
            ->addStoreFilter($this->getRequest()->getParam('store'))
            ->joinField('position',
                'catalog/category_product',
                'position',
                'product_id=entity_id',
                'category_id='.(int) $this->getRequest()->getParam('id', 0),
                'inner')
			->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
		$collection->getSelect()->order("position asc");
		$collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
		$collection->addAttributeToSelect('image');
		$collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);	/// ONLY GET ENABLED
		
		$collection->joinAttribute('special_price', 'catalog_product/special_price', 'entity_id', null, 'left', $store->getId());
	//
        $this->setCollection($collection);
		
//Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection); 		
 

		if (!$this->getCategory()->getId()) {
			$this->getCollection()->addFieldToFilter('entity_id', array('in'=>array()));
			$this->setEmptyText("Please select Or create a category for chainging the product position.");
		}

        if ($this->getCategory()->getProductsReadonly()) {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
        }
 

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
		 $store = $this->_getStore();

	if ($this->getCategory()->getId()) {
			$this->addColumn('position_num', array(
				'header'    => Mage::helper('catalog')->__('Pos.'),
				'width'     => '1',
				'align'     => 'center',
				'type'      => 'number',
				'index'     => 'position',
				'filter'    => false,
				'sortable'  => false,
			));
		}
		
    
        $this->addColumn('thumbnail', array(
			'header'=> Mage::helper('catalog')->__('Thumbnail'),
			'width' => '90px',
			'index' => 'image',
			'renderer'  => 'Magedev_Productposition_Block_Widget_Grid_Column_Renderer_Thumbnail',
			'filter' => false,
			'sortable'  => false
        ));
        $this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Name'),
            'index'     => 'name',
		'filter'    => false,
			'sortable'    => false
        ));
        $this->addColumn('sku', array(
            'header'    => Mage::helper('catalog')->__('SKU'),
            'width'     => '80',
            'index'     => 'sku',
	'filter'    => false,
			'sortable'    => false
        ));
		
		    $this->addColumn('entity_id', array(
            'header'    => Mage::helper('catalog')->__('ID'),
            'sortable'  => false,
            'width'     => '60',
            'index'     => 'entity_id',
			'filter'    => false,
			'sortable'    => false
        ));
        $this->addColumn('price', array(
            'header'    => Mage::helper('catalog')->__('Price'),
            'type'  => 'currency',
            'width'     => '1',
             'index'     => 'price',
	'filter'    => false,
			'sortable'    => false
        ));
		
		$this->addColumn('special_price',
            array(
                'header'=> Mage::helper('catalog')->__('Special Price'),
                'type'  => 'currency', 'width' => '110px',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
                'index' => 'special_price',
				//'filter'    => false,
		'filter'    => false,
			'sortable'    => false
        ));
		
		$this->addColumn('type',
            array(
                'header'=> Mage::helper('catalog')->__('Type'),
                'width' => '60px',
                'index' => 'type_id',
                'type'  => 'options',
				'sortable'    => false,
				'filter'    => false,
                'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        ));
		
		$this->addColumn('inventory', array(
			'header'=> Mage::helper('catalog')->__('Inventory'),
 			'type'  => 'number',
			'index' => 'qty',
	'filter'    => false,
			'sortable'    => false
		));

		$this->addColumn('status', array(
			'header'=> Mage::helper('catalog')->__('Status'),
			'width' => '70px',
			'index' => 'status',
			'type'  => 'options',
			'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
	'filter'    => false,
			'sortable'    => false
		));
		
	
		
		/*
		if ($this->getCategory()->getId()) {
			$this->addColumn('position', array(
				'header'    => Mage::helper('catalog')->__('Drag'),
				'width'     => '1',
				'align'     => 'center',
				'type'      => 'number',
				'index'     => 'position',
				'filter'    => false,
				'sortable'  => false,
				'renderer'  => 'Magedev_Productposition_Block_Widget_Grid_Column_Renderer_Dragable'
			));
		}
		*/

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '70px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Detail Edit'),
                        'url'     => array(
                            'base'=>'*/catalog_product/edit',
                            'params'=>array('store'=>$this->getRequest()->getParam('store'))
                        ),
						'target' =>'BLANK_',
						
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
        ));
		
		
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/enhancedgrid', array('_current'=>true));
    }

    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('selected_products');
        if (is_null($products)) {
            $products = $this->getCategory()->getProductsPosition();
            return array_keys($products);
        }
        return $products;
    }
	
	 protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

}