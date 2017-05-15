<?php

/**
 * A recordset wrapper class for StoreCategoryImage objects
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @see       StoreCategoryImage
 */
class StoreCategoryImageWrapper extends SiteImageWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreCategoryImage');
	}

	// }}}
}

?>
