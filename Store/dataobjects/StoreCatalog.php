<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCatalog extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('clone_of', 'Catalog');

		$this->table = 'Catalog';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
