<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A shipping type for an order for an e-commerce web application
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreShippingType
 */
class StoreOrderShippingType extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier of this shipping type
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Non-visible string indentifier
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible title for this shipping type
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Surcharge price
	 *
	 * @var float
	 */
	public $price;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'OrderShippingType';
	}

	// }}}
}

?>
