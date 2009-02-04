<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * A payment method for an order for an e-commerce Web application
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentMethod
 * @see       StorePaymentMethodTransaction
 * @see       StorePaymentType
 * @see       StoreCardType
 */
class StoreOrderPaymentMethod extends StorePaymentMethod
{
	// {{{ protected properties

	/**
	 * Id of the account payment method this order payment method was created
	 * from
	 *
	 * @var integer
	 */
	protected $account_payment_method_id = null;

	// }}}
	// {{{ public function getAccountPaymentMethodId()

	public function getAccountPaymentMethodId()
	{
		return $this->account_payment_method_id;
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StorePaymentMethod $method)
	{
		parent::copyFrom($method);

		if ($method instanceof StoreAccountPaymentMethod)
			$this->account_payment_method_id = $method->id;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'OrderPaymentMethod';
		$this->registerInternalProperty('ordernum',
			SwatDBClassMap::get('StoreOrder'));
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		$properties = parent::getSerializablePrivateProperties();
		$properties[] = 'account_payment_method_id';

		return $properties;
	}

	// }}}

	// loader methods
	// {{{ protected function loadTransactions()

	protected function loadTransactions()
	{
		$sql = sprintf('select * from PaymentMethodTransaction
			where payment_method = %s
			order by createdate, id',
			$this->db->quote($this->id, 'integer'));

		$transactions = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StorePaymentMethodTransactionWrapper'));

		return $transactions;
	}

	// }}}
}

?>
