<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreLocale.php';

/**
 * A recordset wrapper class for StoreLocale objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreLocale
 */
class StoreLocaleWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreLocale');
	}

	// }}}
}

?>
