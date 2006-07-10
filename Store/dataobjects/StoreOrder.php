<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrderAddress.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';

/**
 *
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrder extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The full name of this customer
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * Snapshot of the customer's email address
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Snapshot of the customer's Phone Number
	 *
	 * @var string
	 */
	public $phone;

	/**
	 * Comments
	 *
	 * @var string
	 */
	public $comments;

	/**
	 * Creation date
	 *
	 * @var date
	 */
	public $createdate;

	/**
	 * Ship to billing address?
	 *
	 * @var boolean
	 */
	public $ship_to_billing_address;

	/**
	 * Shipping amount
	 *
	 * @var float
	 */
	public $shipping;

	/**
	 * Total amount
	 *
	 * @var float
	 */
	public $total;

	/**
	 * Subtotal amount
	 *
	 * @var float
	 */
	public $subtotal;

	// }}}
	// {{{ public function getShippingAddress()

	public function getShippingAddress()
	{
		if ($this->ship_to_billing_address)
			return $this->billing_address;

		return $this->shipping_address;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('account',
			$this->class_map->resolveClass('StoreAccount'));

		$this->registerInternalProperty('billing_address',
			$this->class_map->resolveClass('StoreOrderAddress'));

		$this->registerInternalProperty('shipping_address',
			$this->class_map->resolveClass('StoreOrderAddress'));

		$this->registerInternalProperty('payment_method',
			$this->class_map->resolveClass('StoreOrderPaymentMethod'));

		$this->registerDateProperty('createdate');

		$this->table = 'Orders';
		$this->id_field = 'integer:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadItems()

	protected function loadItems()
	{
		$wrapper = $this->class_map->resolveClass('StoreOrderItemWrapper');
		$sql = 'select * from OrderItem where ordernum = %s';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
}

?>
