<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
class StoreProductWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function setRegion()

	/**
	 * Sets the region for all products in this record set
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to not load this product if it is
	 *                           not available in the given region.
	 */
	public function setRegion(StoreRegion $region, $limiting = true)
	{
		foreach ($this as $product)
			$product->setRegion($region, $limiting);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreProduct');
		$this->index_field = 'id';
	}

	// }}}
}

?>
