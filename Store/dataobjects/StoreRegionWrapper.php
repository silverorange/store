<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * A recordset wrapper class for StoreRegion objects
 *
 * @package   Store 
 * @copyright 2006 silverorange
 */
class StoreRegionWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreRegion');
	}

	// }}}
}

?>
