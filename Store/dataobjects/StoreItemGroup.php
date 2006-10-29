<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * Dataobject to group {@link StoreItem} objects within a {@link StoreProduct}
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
