<?php

/**
 * A recordset wrapper class for ItemRegionBinding objects
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
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
