<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Item alias object
 *
 * @package   Store
 * @copyright 2005-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItemAliasWrapper
 */
class StoreItemAlias extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * unique id,
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * not null references items(id) on delete cascade,
	 *
	 * @var integer
	 */
	public $item;

	/**
	 * not null,
	 *
	 * @var string
	 */
	public $sku;

	// }}}
	// {{{ protected function init()

	/**
	 * Sets up this dataobject
	 */
	protected function init()
	{
		$this->table = 'ItemAlias';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
