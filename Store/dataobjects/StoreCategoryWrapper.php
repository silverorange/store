<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreCategory.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCategoryWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreCategory');

		$this->index_field = 'id';
	}

	// }}}
}

?>
