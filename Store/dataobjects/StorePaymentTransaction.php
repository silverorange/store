<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * A payment transaction for an e-commerce web application
 *
 * Payment transactions are usually tied to {@link StoreOrder} objects. The
 * set of {@link StorePaymentProvider} classes return StorePaymentTransaction
 * objects for most transaction methods.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider
 */
class StorePaymentTransaction extends SwatDBDataObject
{
	// {{{ class constants

	/**
	 * Field was checked and passed checks
	 */
	const STATUS_PASSED     = 0;

	/**
	 * Field was checked and failed checks
	 */
	const STATUS_FAILED     = 1;

	/**
	 * Field may or may not have been provided but was not checked either
	 * because check is not supported by card provider or the developer opted
	 * not to check AVS fields.
	 */
	const STATUS_NOTCHECKED = 2;

	/**
	 * Field was not provided and thus not checked
	 */
	const STATUS_MISSING    = 3;

	// }}}
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
	 * Security key used to validate the <i>$transaction_id</i>
	 *
	 * The security key is not used for every payment provider. For payment
	 * providers that do not use a security key, this property is null.
	 *
	 * @var string
	 */
	public $security_key;

	/**
	 * Authorization code of this transaction
	 *
	 * Some payment providers require this field for 2-part transactions. For
	 * example, a hold-release transaction might require this field.
	 *
	 * @var string
	 */
	public $authorization_code;

	/**
	 * Unique identifier for this transaction supplied by payment provider
	 *
	 * This use used for 3-D Secure transactions. For non-3-D Secure
	 * transactions, this field will be null.
	 *
	 * @var string
	 * @see StorePaymentTransaction::loadFromMerchantData()
	 */
	public $merchant_data;

	/**
	 * The date this transaction was created on
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * Status of address check
	 *
	 * One of StorePaymentTransaction::STATUS_*.
	 *
	 * @var integer
	 */
	public $address_status;

	/**
	 * Status of zip/postal code check
	 *
	 * One of StorePaymentTransaction::STATUS_*.
	 *
	 * @var integer
	 */
	public $postal_code_status;

	/**
	 * Status of card verification value check
	 *
	 * One of StorePaymentTransaction::STATUS_*.
	 *
	 * @var integer
	 */
	public $card_verification_value_status;

	/**
	 * Status of 3-D Secure authentication
	 *
	 * One of StorePaymentTransaction::STATUS_*.
	 *
	 * @var integer
	 */
	public $three_domain_secure_status;

	/**
	 * Cardholder authentication verification value
	 *
	 * This value is used by card providers to prove an authentication occured
	 * for this transaction. It is returned from authenticated 3-D Secure
	 * transactions.
	 *
	 * @var string
	 */
	public $cavv;

	/**
	 * The type of request used to create this transaction
	 *
	 * This should be one of the {@link StorePaymentRequest}::TYPE_* constants.
	 *
	 * @var integer
	 */
	public $request_type;

	// }}}
	// {{{ public function loadFromMerchantData()

	/**
	 * Loads this transaction from merchant data
	 *
	 * This method is used to get the appropriate transaction object for a
	 * 3-D Secure authentication response from the cardholder's bank. This is
	 * needed, for example, to get order information from the 3-D Secure
	 * authentication response. Merchant data uniquely identifies a 3-D Secure
	 * transaction. If there is more than one transaction in the database with
	 * the specified merchant data, the most recent transaction is loaded.
	 *
	 * @param string $merchant_data the merchant data of the transaction to
	 *                               load.
	 *
	 * @return boolean true if this transaction could be loaded from the
	 *                  specified merchant data and false if it could not.
	 */
	public function loadFromMerchantData($merchant_data)
	{
		$this->checkDB();

		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where merchant_data = %s
				order by createdate desc limit 1',
				$this->table,
				$this->db->quote($merchant_data, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();
		return true;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';
		$this->table = 'PaymentTransaction';
		$this->registerDateProperty('createdate');
		$this->registerInternalProperty('ordernum',
			SwatDBClassMap::get('StoreOrder'));

	}

	// }}}
}

?>
