<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreCategory.php';

/**
 * A recordset wrapper fot StoreCategory objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCategory
 */
class StoreCategoryWrapper extends StoreRecordsetWrapper
{
	// {{{ public function setRegion()

	/**
	 * Sets the region for all categories in this record set
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to not load this category if it
	 *                           is not available in the given region.
	 */
	public function setRegion(StoreRegion $region, $limiting = true)
	{
		foreach ($this as $category)
			$category->setRegion($region, $limiting);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreCategory');

		$this->index_field = 'id';
	}

	// }}}
}

?>
