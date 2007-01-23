<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreQuantityDiscountRegionBinding.php';

/**
 * A recordset wrapper class for QuantityDiscountRegionBinding objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityDiscountRegionBindingWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = $this->class_map->resolveClass(
			'StoreQuantityDiscountRegionBinding');
	}

	// }}}
}

?>
