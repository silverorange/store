<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';

/**
 * A transaction for particular payment on an e-commerce Web application
 *
 * The set of {@link StorePaymentProvider} classes return
 * StorePaymentMethodTransaction objects for most transaction methods.
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider
 * @see       StoreOrderPaymentMethod
 */
class StorePaymentMethodTransaction extends SwatDBDataObject
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
	 * The date this transaction was created on
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * The type of this transaction
	 *
	 * This should be one of the {@link StorePaymentRequest}::TYPE_* constants.
	 *
	 * @var integer
	 */
	public $transaction_type;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';
		$this->table = 'PaymentMethodTransaction';
		$this->registerDateProperty('createdate');
		$this->registerInternalProperty('payment_method',
			SwatDBClassMap::get('StoreOrderPaymentMethod'));

	}

	// }}}
}

?>
