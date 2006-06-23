<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreOrderItem.php';

/**
 * A recordset wrapper class for StoreOrderItem objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreOrderItem
 */
class StoreOrderItemWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'id';
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreOrderItem');
	}

	// }}}
}

?>
