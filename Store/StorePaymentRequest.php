<?php

require_once 'Store/exceptions/StoreException.php';

/**
 * Base class for online financial transaction requests
 *
 * The basic pattern for making online transactions is:
 *
 * 1. create a request object
 * 2. set protocol-specific fields on the request object
 * 3. process the request to get a response object
 * 4. get protocol-specific fields from the response object
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StorePaymentRequest
{
	// {{{ class constants

	/**
	 * A normal payment request 
	 *
	 * Funds are requested immediately and collected during the next bank
	 * processing period or sooner if the payment provider supports it.
	 */
	const TYPE_PAY         = 0;

	/**
	 * A credit card verification request
	 *
	 * An verification does not collect funds. An verification will return
	 * transaction information that can be used to collect funds at a later
	 * date without requiring the user to re-enter their payment details.
	 */
	const TYPE_VERIFY      = 1;

	/**
	 * A credit or refund request
	 *
	 * Instead of collecting funds from the card holder, funds are transferred
	 * to the card holder.
	 */
	const TYPE_REFUND      = 2;

	/**
	 * A post-verified payment request
	 *
	 * Funds are requested immediately using verified transaction information.
	 * The transaction information should be from a previous
	 * {@link StorePaymentRequest::TYPE_VERIFY} request.
	 */
	const TYPE_VERIFIEDPAY = 3;

	/**
	 * A cancel transaction request
	 *
	 * Cancels any type of transaction. The cancel request must be made before
	 * the payment provider's backend payment system completes the transaction
	 * (usually happens once a day). If the payment provider's backend payment
	 * system has already processed the transaction, the void request will
	 * fail.
	 */
	const TYPE_VOID        = 4;

	/**
	 * A deferred payment request
	 *
	 * Places a shadow on card holder's funds for a few days. When the payment
	 * is ready to be collected, the funds should be released using a
	 * {@link StorePaymentRequest::TYPE_RELEASE} request. If the transaction
	 * should not be completed, use a {@link StorePaymentRequest::TYPE_ABORT}
	 * request.
	 */
	const TYPE_HOLD        = 5;

	/**
	 * A release deferred funds request
	 *
	 * Releases funds shadowed by a deferred payment request using a deferred
	 * transaction id.
	 */
	const TYPE_RELEASE     = 6;

	/**
	 * An abort deferred funds request
	 *
	 * Aborts a deferred payment request using a deferred transaction id. The
	 * shadow is removed from the card-holder's card and a release may no
	 * longer be made on the deferred transaction.
	 */
	const TYPE_ABORT       = 7;

	/**
	 * A status request
	 *
	 * Requests the status of an existing transaction.
	 */
	const TYPE_STATUS      = 8;

	/**
	 * A Three Domain Secure (3-DS) authentication request
	 *
	 * Gets authentication information from an existing 3-DS transaction.
	 */
	const TYPE_3DS_AUTH    = 9;

	// }}}
	// {{{ protected properties

	/**
	 * The type of request
	 *
	 * One of the StorePaymentRequest::TYPE_* constants.
	 *
	 * @var integer
	 */
	protected $type;

	/**
	 * The transaction mode for this request
	 *
	 * Transaction modes let you switch between live, test and other modes of
	 * transaction. The mode should be one of the modes returned by
	 * {@link StorePaymentRequest::getAvailableModes()}.
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * Protocol specific data
	 *
	 * This is an array with index names representing protocol fields and
	 * values representing protocol values.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * An array or fields required by this request's protocol
	 *
	 * This array may be modified by different methods that dictate more or
	 * fewer fields are required based on protocol-specific rules.
	 *
	 * @var array
	 */
	protected $required_fields = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment request
	 *
	 * @param integer $type the type of payment request to make. Should be one
	 *                       of the StorePaymentRequest::TYPE_* constants.
	 * @param string $mode the transaction mode to use. Should be one of the
	 *                      values returned by
	 *                      {@link StorePaymentRequest::getAvailableModes()}.
	 *
	 * @throws StoreException if the type or the mode is invalid.
	 */
	public function __construct($type, $mode)
	{
		if (!in_array($mode, $this->getAvailableModes()))
			throw new StoreException(sprintf("Invalid mode '%s' for payment ".
				"request. Valid modes are: '%s'.",
				$mode,
				implode("', '", $this->getAvailableModes())));

		if (!in_array($type, $this->getAvailableTypes())) {
			$available_types = $this->getAvailableTypes();
			$available_type_strings = array();
			foreach ($available_types as $available_type) {
				$available_type_strings[] =
					self::getTypeString($available_type);
			}
			throw new StoreException(sprintf("Invalid request type: %s. ".
				"Valid types are: %s.",
				self::getTypeString($type),
				implode(', ', $available_type_strings)));
		}

		$this->mode = $mode;
		$this->type = $type;

		$this->data = $this->getDefaultData();
	}

	// }}}
	// {{{ public function setField()

	/**
	 * Sets a request field
	 *
	 * @param string $name the name of the field to set. Valid names are
	 *                      found in the integration manual for the payment
	 *                      provider.
	 * @param mixed $value the value to set for the field.
	 */
	public function setField($name, $value)
	{
		$this->data[$name] = $value;
	}

	// }}}
	// {{{ public function setFields()

	/**
	 * Sets multiple request fields
	 *
	 * @param array $fields an array of name-value pairs where the name is
	 *                       a field name and the value is the value to set
	 *                       for the field.
	 */
	public function setFields(array $fields)
	{
		foreach ($fields as $name => $value)
			$this->data[$name] = $value;
	}

	// }}}
	// {{{ public static function getTypeString()

	/**
	 * Gets a human-readable string representing a request type
	 *
	 * @param integer $type a request type. One of the
	 *                       StorePaymentRequest::TYPE_* constants.
	 */
	public static function getTypeString($type)
	{
		$string = 'unknown payment type';

		switch ($type) {
		case StorePaymentRequest::TYPE_PAY:
			$string = 'payment';
			break;
		case StorePaymentRequest::TYPE_VERIFY:
			$string = 'authorization';
			break;
		case StorePaymentRequest::TYPE_REFUND:
			$string = 'refund';
			break;
		case StorePaymentRequest::TYPE_VERIFIEDPAY:
			$string = 'post-authorization payment';
			break;
		case StorePaymentRequest::TYPE_VOID:
			$string = 'void (cancel)';
			break;
		case StorePaymentRequest::TYPE_HOLD:
			$string = 'hold';
			break;
		case StorePaymentRequest::TYPE_RELEASE:
			$string = 'release';
			break;
		case StorePaymentRequest::TYPE_ABORT:
			$string = 'abort';
			break;
		case StorePaymentRequest::TYPE_STATUS:
			$string = 'status';
			break;
		}

		return $string;
	}

	// }}}
	// {{{ abstract public function process()

	/**
	 * Processes this request
	 *
	 * Subclasses implement this method to perform protocol-specific
	 * processing of fields and values.
	 *
	 * @return StorePaymentResponse the response from the payment provider for
	 *                               this request.
	 */
	abstract public function process();

	// }}}
	// {{{ protected function makeFieldRequired()

	/**
	 * Makes a field required
	 *
	 * @param string $field_name the name of the field to make required.
	 */
	protected function makeFieldRequired($field_name)
	{
		if (!in_array($field_name, $this->required_fields))
			$this->required_fields[] = $field_name;
	}

	// }}}
	// {{{ protected function makeFieldsRequired()

	/**
	 * Makes a list of fields required
	 *
	 * @param array $field_names a list of field names to make required.
	 */
	protected function makeFieldsRequired(array $field_names)
	{
		foreach ($field_names as $field_name)
			$this->makeFieldRequired($field_name);
	}

	// }}} 
	// {{{ protected function checkRequiredFields()

	/**
	 * Ensures all required fields are set on this request
	 *
	 * @throws StoreException A StoreException is thrown if a required field
	 *                        is not set.
	 */
	protected function checkRequiredFields()
	{
		foreach ($this->required_fields as $field_name) {
			if (!array_key_exists($field_name, $this->data)) {
				throw new StoreException(sprintf("Missing required field %s.",
					$field_name));
			}
		}
	}

	// }}} 
	// {{{ protected function getAvailableModes()

	/**
	 * Gets a list of available transaction modes for this request
	 *
	 * @return array a list of available transaction modes for this request.
	 */
	protected function getAvailableModes()
	{
		return array(
			'test',
			'live',
		);
	}

	// }}}
	// {{{ protected function getAvailableTypes()

	/**
	 * Gets a list of available transaction types for this request
	 *
	 * By default, available types are taken from
	 * {@link StorePaymentRequest::getTypeMap()} method.
	 */
	protected function getAvailableTypes()
	{
		return array_keys($this->getTypeMap());
	}

	// }}}
	// {{{ protected function getDefaultData()

	/**
	 * Gets a key-value array of protocol-specific default data
	 *
	 * By default, no default data is specified. Subclasses should override
	 * this method to define default data.
	 *
	 * @return array a key-value array of protocol-specific default data. The
	 *                key is a protocol field and the value is the default
	 *                value to use for the field.
	 */
	protected function getDefaultData()
	{
		return array();
	}

	// }}}
	// {{{ abstract protected function __toString()

	/**
	 * Gets a string representation of this payment request
	 *
	 * This is primarily useful for debugging and/or logging.
	 *
	 * @return string a string representation of this payment request.
	 */
	abstract protected function __toString();

	// }}}
	// {{{ abstract protected function getTypeMap()

	/**
	 * Gets a mapping of valid request types for this request to
	 * protocol-specific trasaction types
	 *
	 * The array is indexed by StorePaymentRequest::TYPE_* constants and the
	 * values are protocol-specific transaction types.
	 *
	 * @return array a mapping of valid request types to protocol-specific
	 *                transaction types.
	 */
	abstract protected function getTypeMap();

	// }}}
	// {{{ abstract protected function getDefaultRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required by default
	 *
	 * @return array a list of protocol-specific fields that are required by
	 *                default.
	 */
	abstract protected function getDefaultRequiredFields();

	// }}}
}

?>
