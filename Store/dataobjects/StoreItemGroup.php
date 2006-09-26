<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/*
 * @package   Store
 * @copyright silverorange 2005
 */
class StoreItemGroup extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Id of Product with ItemGroup belongs to
	 *
	 * @var integer
	 */
	public $product;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ItemGroup';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
