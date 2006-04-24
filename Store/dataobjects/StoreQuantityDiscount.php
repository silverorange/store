<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Quantity discount object
 *
 * @package   Store 
 * @copyright 2006 silverorange
 */
class StoreQuantityDiscount extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * not null references items(id),
	 *
	 * @var integer
	 */
	public $item;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $quantity;

	// }}}
}

?>
