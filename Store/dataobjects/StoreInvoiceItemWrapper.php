<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreInvoiceItem.php';

/**
 * A recordset wrapper class for StoreInvoiceItem objects
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @see       StoreInvoiceItem
 */
class StoreInvoiceItemWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'id';
		$this->row_wrapper_class = SwatDBClassMap::get('StoreInvoiceItem');
	}

	// }}}
}

?>
