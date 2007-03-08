<?php

require_once 'Swat/SwatObject.php';

/**
 * Abstract base class for an object status
 *
 * This class forms the base of StoreItemStatus and StoreOrderStatus.
 *
 * Making statuses objects instead of simple constants has a few advantages
 * that are not immediately obvious:
 *
 * First, it is easier to define a base set of statuses in the Store package
 * and have site code provide additional statuses. Alternatives to this
 * technique are having no statuses defined in Store, resulting in unecessarily
 * abstract objects, and having a mix of constant namespaces
 * (StoreOrder::STATUS_AUTHORIZED, MyOrder::STATUS_READYTOSHIP).
 *
 * A second advantage is that information about an object's statuses can easily
 * be retrieved from the object itself. For example, it is easy to get the
 * textual description of an order status:
 *
 * <code>
 * echo $order->getStatus()->title;
 * </code>
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreStatus extends SwatObject
{
	// {{{ public properties

	/**
	 * The 'value' of this status
	 *
	 * This is often what is saved in the database for an object.
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title of this status
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Mnemonic shortname used primarily when assigning statuses to objects
	 * or when writing conditional code
	 *
	 * @var string
	 */
	public $shortname;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new status object
	 *
	 * @param integer $id the valud of this status.
	 * @param string $shortname the mnemonic shortname of this status.
	 * @param string $title the user visible title of this status.
	 */
	public function __construct($id, $shortname, $title)
	{
		$this->id = (integer)$id;
		$this->shortname = (string)$shortname;
		$this->title = (string)$title;
	}

	// }}}
}

?>
