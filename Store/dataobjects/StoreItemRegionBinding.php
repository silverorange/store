<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * Dataobject for item region bindings
 *
 * @package   Store
 * @copyright silverorange 2006
 */
class StoreItemRegionBinding extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Price of the item
	 *
	 * @var float
	 */
	public $price;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('region',
			$this->class_map->resolveClass('StoreRegion'));

		$this->registerInternalProperty('item',
			$this->class_map->resolveClass('StoreItem'));

		$this->table = 'ItemRegionBinding';
	}

	// }}}
}

?>
