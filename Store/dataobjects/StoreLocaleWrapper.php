<?php


/**
 * A recordset wrapper class for StoreLocale objects
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @see       StoreLocale
 */
class StoreLocaleWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'id';
		$this->row_wrapper_class = SwatDBClassMap::get('StoreLocale');
	}

	// }}}
}

?>
