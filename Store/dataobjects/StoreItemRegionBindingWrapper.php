<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItemRegionBinding.php';

/**
 * A recordset wrapper class for ItemRegionBinding objects
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreItemRegionBindingWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreItemRegionBinding');
	}

	// }}}
}

?>
