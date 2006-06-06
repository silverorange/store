<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreAccountAddress.php';

/**
 * A recordset wrapper class for StoreAccountAddress objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreAccountAddress
 */
class StoreAccountAddressWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreAccountAddress');

		$this->index_field = 'id';
	}

	// }}}
}

?>
