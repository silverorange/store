<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItemProvStateExclusionBinding.php';

/**
 * A recordset wrapper class for StoreItemProvStateExclusionBinding objects
 *
 * @package   Store
 * @copyright 2012-2016 silverorange
 */
class StoreItemProvStateExclusionBindingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			SwatDBClassMap::get('StoreItemProvStateExclusionBinding');
	}

	// }}}
}

?>
