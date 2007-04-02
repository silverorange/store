<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreInvoice.php';

/**
 * A recordset wrapper class for StoreInvoice objects
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @see       StoreInvoice
 */
class StoreInvoiceWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = $this->class_map->resolveClass('StoreInvoice');
		$this->index_field = 'id';
	}

	// }}}
}

?>
