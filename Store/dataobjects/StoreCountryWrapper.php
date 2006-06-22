<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreCountry.php';

/**
 * A recordset wrapper class for StoreCountry objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreCountry
 */
class StoreCountryWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreCountry');
	}

	// }}}
}

?>
