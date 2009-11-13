<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItemMinimumQuantityGroup.php';

/**
 * A recordset wrapper class for StoreItemMinimumQuantityGroup objects
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @see       StoreItemMinimumQuantityGroup
 */
class StoreItemMinimumQuantityGroupWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			SwatDBClassMap::get('StoreItemMinimumQuantityGroup');

		$this->index_field = 'id';
	}

	// }}}
}

?>
