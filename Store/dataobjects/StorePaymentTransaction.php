<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * A payment transaction for an e-commerce web application
 *
 * Payment transactions are usually tied to {@link StoreOrder} objects. The
 * set of {@link StorePaymentProvider} classes return StorePaymentTransaction
 * objects for most transactions.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider
 */
class StorePaymentMethod extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Payment transaction identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The payment-provider specific transaction identifier
	 *
	 * @var string
	 */
	public $transaction_id;

	/**
	 * Security key used to validate the <i>transaction_id</i>
	 *
	 * The security key is not used for every payment provider. For payment
	 * providers that do not use a security key, this property is null.
	 *
	 * @var string
	 */
	public $security_key;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
