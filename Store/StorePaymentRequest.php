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
	const TYPE_NORMAL   = 0;

	/**
	 * A credit card authentication request
	 *
	 * An authentication does not collect funds. An authentication will return
	 * a transaction id that can be used to collect funds at a later date
	 * without requiring the user to re-enter their payment details.
	 */
	const TYPE_AUTH     = 1;

	/**
	 * A credit or refund request
	 *
	 * Instead of collecting funds from the card holder, funds are transferred
	 * to the card holder.
	 */
	const TYPE_CREDIT   = 2;

	/**
	 * A post-authorized payment request
	 *
	 * Funds are requested immediately using an authenticated transaction id.
	 * The transaction id should be from a previous
	 * {@link StorePaymentRequest::TYPE_AUTH} request.
	 */
	const TYPE_POSTAUTH = 3;

	/**
	 * A cancel transaction request
	 *
	 * Cancels any type of transaction. The cancel request must be made before
	 * the payment provider's backend payment system completes the transaction
	 * (usually happens once a day). If the payment provider's backend payment
	 * system has already processed the transaction, the void request will
	 * fail.
	 */
	const TYPE_VOID     = 4;

	/**
	 * A deferred payment request
	 *
	 * Places a shadow on card holder's funds for a few days. When the payment
	 * is ready to be collected, the funds should be released using a
	 * {@link StorePaymentRequest::TYPE_RELEASE} request.
	 */
	const TYPE_DEFERRED = 5;

	/**
	 * A release deferred funds request
	 *
	 * Released funds shadowed by a deferred payment request using a deferred
	 * transaction id. 
	 */
	const TYPE_RELEASE  = 6;

	// }}}
	// {{{ protected properties

	protected $type;
	protected $mode;
	protected $data = array();
	protected $required_fields = array();

	// }}}
	// {{{ public function __construct()

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
					$this->getTypeString($available_type);
			}
			throw new StoreException(sprintf("Invalid request type: %s. ".
				"Valid types are: %s.",
				$this->getTypeString($type),
				implode(', ', $available_type_strings)));
		}

		$this->mode = $mode;
		$this->type = $type;

		$this->data = $this->getDefaultData();
	}

	// }}}
	// {{{ public function setField()

	public function setField($name, $value)
	{
		$this->data[$name] = $value;
	}

	// }}}
	// {{{ public function setFields()

	public function setFields(array $fields)
	{
		foreach ($fields as $name => $value)
			$this->data[$name] = $value;
	}

	// }}}
	// {{{ public function getTypeString()

	public function getTypeString($type)
	{
		$string = 'unknown payment type';

		switch ($type) {
		case StorePaymentRequest::TYPE_NORMAL:
			$string = 'normal (payment)';
			break;
		case StorePaymentRequest::TYPE_AUTH:
			$string = 'authorization';
			break;
		case StorePaymentRequest::TYPE_CREDIT:
			$string = 'credit (refund)';
			break;
		case StorePaymentRequest::TYPE_POSTAUTH:
			$string = 'post-authorization payment';
			break;
		case StorePaymentRequest::TYPE_VOID:
			$string = 'void (cancel)';
			break;
		case StorePaymentRequest::TYPE_DEFERRED:
			$string = 'deferred';
			break;
		case StorePaymentRequest::TYPE_RELEASE:
			$string = 'release';
			break;
		}

		return $string;
	}

	// }}}
	// {{{ abstract public function process()

	abstract public function process();

	// }}}
	// {{{ protected function makeFieldRequired()

	protected function makeFieldRequired($field_name)
	{
		if (!in_array($field_name, $this->required_fields))
			$this->required_fields[] = $field_name;
	}

	// }}}
	// {{{ protected function makeFieldsRequired()

	protected function makeFieldsRequired(array $field_names)
	{
		foreach ($field_names as $field_name)
			$this->makeFieldRequired($field_name);
	}

	// }}} 
	// {{{ protected function checkRequiredFields()

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

	protected function getAvailableModes()
	{
		return array(
			'test',
			'live',
		);
	}

	// }}}
	// {{{ protected function getAvailableTypes()

	protected function getAvailableTypes()
	{
		return array_keys($this->getTypeMap());
	}

	// }}}
	// {{{ abstract protected function __toString()

	abstract protected function __toString();

	// }}}
	// {{{ abstract protected function getTypeMap()

	abstract protected function getTypeMap();

	// }}}
	// {{{ abstract protected function getDefaultData()

	abstract protected function getDefaultData();

	// }}}
	// {{{ abstract protected function getDefaultRequiredFields()

	abstract protected function getDefaultRequiredFields();

	// }}}
}

?>
