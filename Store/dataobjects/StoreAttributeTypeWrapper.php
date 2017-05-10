<?php


/**
 * A recordset wrapper class for StoreAttributeType objects
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 * @see       StoreAttributeType
 */
class StoreAttributeTypeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreAttributeType');
	}

	// }}}
}

?>
