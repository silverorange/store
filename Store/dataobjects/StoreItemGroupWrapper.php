<?php


/**
 * A recordset wrapper class for StoreItemGroup objects
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @see       StoreItemGroup
 */
class StoreItemGroupWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreItemGroup');

		$this->index_field = 'id';
	}

	// }}}
}

?>
