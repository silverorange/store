<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItemRegionBinding.php';

/**
 * A recordset wrapper class for ItemRegionBinding objects
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
class StoreItemRegionBindingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			SwatDBClassMap::get('StoreItemRegionBinding');
	}

	// }}}
}

?>
