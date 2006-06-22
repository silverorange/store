<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreQuantityDiscount.php';

/**
 * A recordset wrapper class for StoreQuantityDiscount objects
 *
 * @package   Store 
 * @copyright 2006 silverorange
 */
class StoreQuantityDiscountWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreQuantityDiscount');

		$this->index_field = 'id';
	}

	// }}}
}

?>
