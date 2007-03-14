<?php

require_once 'Store/StorePaymentRequest.php';
require_once 'Store/StoreProtxPaymentResponse.php';

/**
 * Payment request class for making payments with Protx VSP Direct
 *
 * The transaction protocol used is VSP Direct. See the Protx VSP Direct
 * integration guidelines at:
 * {@link http://www.protx.com/downloads/docs/VSPDirectProtocolandIntegrationGuideline.pdf}.
 *
 * This class allows you to manipulate VSP Direct requests at the protocol
 * level. For higher-level payment processing, use the set of
 * {@link StorePaymentProvider} classes.
 *
 * This class relies on the CURL extension which is included with PHP in
 * PHP version 5.1 or greater.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProtxPaymentRequest extends StorePaymentRequest
{
	// {{{ class constants

	/**
	 * URL of the simulator mode transaction processor
	 */
	const URL_SIMULATOR =
		'https://ukvpstest.protx.com/VSPSimulator/VSPDirectGateway.asp';

	/**
	 * URL for processing test mode payment transactions
	 */
	const URL_TEST_PAYMENT =
		'https://ukvpstest.protx.com/vpsDirectAuth/PaymentGateway3D.asp';

	/**
	 * URL for processing live mode payment transactions
	 */
	const URL_LIVE_PAYMENT =
		'https://ukvps.protx.com/vpsDirectAuth/PaymentGateway3D.asp';

	/**
	 * URL for processing test mode transactions other than payment
	 * transactions
	 */
	const URL_TEST =
		'https://ukvpstest.protx.com/vps200/dotransaction.dll?Service=Vendor%sTx';

	/**
	 * URL for processing live mode transactions other than payment
	 * transactions
	 */
	const URL_LIVE =
		'https://ukvps.protx.com/vps200/dotransaction.dll?Service=Vendor%sTx';

	/**
	 * URL for getting live transaction status
	 *
	 * No such feature exists for the test or simulator modes.
	 */
	const URL_LIVE_STATUS = 'https://ukvps.protx.com/txstatus/txstatus.asp';

	// }}}
	// {{{ public properties

	/**
	 * Default mode to use for transactions
	 *
	 * This static property can be set once by an application to control the
	 * default behaviour of all Protx payment requests.
	 *
	 * @var string
	 */
	public static $default_mode = 'simulator';

	// }}}
	// {{{ private properties

	/**
	 * The CURL handle
	 *
	 * This payment request class uses CURL internally to communicate with
	 * Protx servers.
	 *
	 * @var resource
	 */
	private $curl_handle;

	/**
	 * The URL at which requests are processed
	 *
	 * @var string
	 */
	private $url;

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
	 *                      By default this is
	 *                      {@link StoreProtxPaymentRequest::$default_mode}.
	 *
	 * @throws StoreException if the type or the mode is invalid.
	 */
	public function __construct($type = StorePaymentRequest::TYPE_PAY,
		$mode = null)
	{
		if ($mode === null)
			$mode = self::$default_mode;

		$this->makeFieldsRequired($this->getDefaultRequiredFields());

		parent::__construct($type, $mode);

		if ($type != StorePaymentRequest::TYPE_STATUS) {
			$type_map = $this->getTypeMap();
			$tx_type = $type_map[$type];

	 		// For Protx VSP Direct, the protocol version is defaulted to
			// '2.22'.
			$this->setField('VPSProtocol', '2.22');
			$this->setField('TxType', $tx_type);
		}

		$payment_types = array(
			StorePaymentRequest::TYPE_VERIFY,
			StorePaymentRequest::TYPE_HOLD,
			StorePaymentRequest::TYPE_PAY,
		);

		switch ($this->mode) {
		case 'simulator':
			if ($type == StorePaymentRequest::TYPE_STATUS)
				echo 'Not supported';
				//$this->url = self::URL_LIVE_STATUS;
			else
				$this->url = self::URL_SIMULATOR; 

			break;
		case 'test':
			if ($type == StorePaymentRequest::TYPE_STATUS)
				echo 'Not supported';
				//$this->url = self::URL_LIVE_STATUS;
			else
				$this->url = (in_array($type, $payment_types)) ?
					self::URL_TEST_PAYMENT :
					sprintf(self::URL_TEST, ucfirst(strtolower($tx_type)));

			break;
		case 'live':
			if ($type == StorePaymentRequest::TYPE_STATUS)
				$this->url = self::URL_LIVE_STATUS;
			else
				$this->url = (in_array($type, $payment_types)) ?
					self::URL_LIVE_PAYMENT :
					sprintf(self::URL_LIVE, ucfirst(strtolower($tx_type)));

			break;
		}

		// make additional fields required based on request type
		switch ($this->type) {
		case StorePaymentRequest::TYPE_PAY:
		case StorePaymentRequest::TYPE_HOLD:
			$this->makeFieldsRequired($this->getPaymentRequiredFields());
			break;
		case StorePaymentRequest::TYPE_RELEASE:
			$this->makeFieldsRequired($this->getReleaseRequiredFields());
			break;
		case StorePaymentRequest::TYPE_ABORT:
			$this->makeFieldsRequired($this->getAbortRequiredFields());
			break;
		case StorePaymentRequest::TYPE_VOID:
			$this->makeFieldsRequired($this->getVoidRequiredFields());
			break;
		case StorePaymentRequest::TYPE_REFUND:
			$this->makeFieldsRequired($this->getRefundRequiredFields());
			break;
		case StorePaymentRequest::TYPE_STATUS:
			$this->makeFieldsRequired($this->getStatusRequiredFields());
			break;
		}

		$this->curl_handle = curl_init();
		curl_setopt_array($this->curl_handle, array(
			CURLOPT_URL  => $this->url,
			CURLOPT_POST => 1,
			CURLOPT_RETURNTRANSFER => 1,
			));
	}

	// }}}
	// {{{ public function process

	/**
	 * Processes this request
	 *
	 * @return StoreProtxPaymentResponse the response from the payment provider
	 *                                    for this request.
	 *
	 * @throws StoreException If the payment server responds with a text/html
	 *                        document instead of a text/plain document. This
	 *                        occurs if something went terribly wrong. The
	 *                        exception message should explain what is wrong
	 *                        from Protx's perspective.
	 */
	public function process()
	{
		// Solo, Switch and American Express require a start date
		if (isset($this->data['CardType']) && in_array($this->data['CardType'],
			array('SOLO', 'SWITCH', 'AMEX'))) {
			$this->makeFieldRequired('StartDate');
		}

		// Require address verification fields when AVS is forced on. These
		// fields may or may not be required when the default ApplyAVSCV2
		// setting is used.
		if (isset($this->data['ApplyAVSCV2']) && in_array(
			$this->data['ApplyAVSCV2'], array(1, 3))) {
			$this->makeFieldsRequired(
				array('CV2', 'BillingAddress', 'BillingPostCode'));
		}

		$this->checkRequiredFields();

		curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS,
			$this->getPostFields());

		$response_text = curl_exec($this->curl_handle);

		set_error_handler(create_function('$errno, $errstr', ''),
			E_NOTICE | E_WARNING);

		$document = new DOMDocument();
		$document->loadHTML($response_text);

		restore_error_handler();

		if ($document->nodeName == 'html') {
			throw new StoreException(sprintf('Received an error page as a '.
				"response. Error contents are: '%s'.",
				$this->parseErrorPage($document)));
		}

		return new StoreProtxPaymentResponse($response_text);
	}

	// }}}
	// {{{ public function __destruct()

	/**
	 * Cleans up resources used by this request
	 */
	public function __destruct()
	{
		curl_close($this->curl_handle);
	}

	// }}}
	// {{{ protected function __toString()

	/**
	 * Gets a string representation of this payment request
	 *
	 * This is primarily useful for debugging and/or logging.
	 *
	 * @return string a string representation of this payment request.
	 */
	protected function __toString()
	{
		$string = sprintf("Request URL: %s\n\n", $this->url);
		foreach ($this->data as $name => $value)
			$string.= sprintf("%s=%s\n", $name, $value);

		return $string;
	}

	// }}}
	// {{{ protected function getAvailableModes()

	/**
	 * Gets a list of available transaction modes for this request
	 *
	 * Protx VSP Direct has three modes:
	 * 1. simulator - this mode lets you test different error situations as
	 *                well as regular transactions.
	 * 2. test      - this mode has the same interface as the live mode but
	 *                no transactions actually take place.
	 * 3. live      - used for making real transactions.
	 *
	 * @return array a list of available transaction modes for this request.
	 */
	protected function getAvailableModes()
	{
		$modes = parent::getAvailableModes();
		$modes[] = 'simulator';
		return $modes;
	}

	// }}}
	// {{{ protected function getTypeMap()

	/**
	 * Gets a mapping of valid request types for this request to
	 * protocol-specific trasaction types
	 *
	 * See the VSP Direct Integration Guidelines document for details.
	 *
	 * The array is indexed by StorePaymentRequest::TYPE_* constants and the
	 * values are protocol-specific transaction types.
	 *
	 * @return array a mapping of valid request types to protocol-specific
	 *                transaction types.
	 */
	protected function getTypeMap()
	{
		static $type_map = array(
			StorePaymentRequest::TYPE_PAY           => 'PAYMENT',
			StorePaymentRequest::TYPE_VERIFY        => 'AUTHORIZE',
			StorePaymentRequest::TYPE_REFUND        => 'CREDIT',
			StorePaymentRequest::TYPE_VERIFIEDPAY   => 'REPEAT', // TODO
			StorePaymentRequest::TYPE_VOID          => 'VOID',
			StorePaymentRequest::TYPE_HOLD          => 'DEFERRED',
			StorePaymentRequest::TYPE_RELEASE       => 'RELEASE',
			StorePaymentRequest::TYPE_ABORT         => 'ABORT',
			// no protocol-specific type exists for status
			StorePaymentRequest::TYPE_STATUS        => '',
		);

		return $type_map;
	}

	// }}}
	// {{{ protected function getDefaultRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required by default
	 *
	 * See the VSP Direct Integration Guidelines document for details.
	 *
	 * @return array a list of protocol-specific fields that are required by
	 *                default.
	 */
	protected function getDefaultRequiredFields()
	{
		static $default_required_fields = array(
			'Vendor',
			'VendorTxCode',
		);

		return $default_required_fields;
	}

	// }}}
	// {{{ protected function getPaymentRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required for payment
	 *
	 * See the VSP Direct Integration Guidelines document for details.
	 *
	 * @return array a list of protocol-specific fields that are required for
	 *                payment.
	 */
	protected function getPaymentRequiredFields()
	{
		static $payment_required_fields = array(
			'VPSProtocol',
			'TxType',
			'Amount',
			'Currency',
			'Description',
			'CardHolder',
			'CardNumber',
			'ExpiryDate',
			'CardType',
		);

		return $payment_required_fields;
	}

	// }}}
	// {{{ protected function getReleaseRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required for a release
	 *
	 * See the VSP Direct Integration Guidelines document for details.
	 *
	 * @return array a list of protocol-specific fields that are required for
	 *                a release.
	 */
	protected function getReleaseRequiredFields()
	{
		static $release_required_fields = array(
			'VPSProtocol',
			'TxType',
			'VPSTxId',
			'SecurityKey',
			'TxAuthNo',
		);

		return $release_required_fields;
	}

	// }}}
	// {{{ protected function getAbortRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required for an abort
	 *
	 * See the VSP Direct Integration Guidelines document for details.
	 *
	 * @return array a list of protocol-specific fields that are required for
	 *                an abort.
	 */
	protected function getAbortRequiredFields()
	{
		static $abort_required_fields = array(
			'VPSProtocol',
			'TxType',
			'VPSTxId',
			'SecurityKey',
			'TxAuthNo',
		);

		return $abort_required_fields;
	}

	// }}}
	// {{{ protected function getRefundRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required for a refund
	 *
	 * See the VSP Direct Integration Guidelines document for details.
	 *
	 * @return array a list of protocol-specific fields that are required for
	 *                a refund.
	 */
	protected function getRefundRequiredFields()
	{
		static $refund_required_fields = array(
			'VPSProtocol',
			'TxType',
			'Amount',
			'Currency',
			'Description',
			'RelatedVPSTxId',
			'RelatedVendorTxCode',
			'RelatedSecurityKey',
			'RelatedTxAuthNo',
		);

		return $refund_required_fields;
	}

	// }}}
	// {{{ protected function getVoidRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required for a void
	 *
	 * See the VSP Direct Integration Guidelines document for details.
	 *
	 * @return array a list of protocol-specific fields that are required for
	 *                a void.
	 */
	protected function getVoidRequiredFields()
	{
		static $void_required_fields = array(
			'VPSProtocol',
			'TxType',
			'VPSTxId',
			'SecurityKey',
			'TxAuthNo',
		);

		return $void_required_fields;
	}

	// }}}
	// {{{ protected function getStatusRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required for a status
	 * request
	 *
	 * There is no documentation online for this feature. The required fields
	 * are 'Vendor' and 'VendorTxCode'.
	 *
	 * @return array a list of protocol-specific fields that are required for
	 *                a status request.
	 */
	protected function getStatusRequiredFields()
	{
		return array();
	}

	// }}}
	// {{{ private function getPostFields()

	/**
	 * Gets the fields of this request as urlencoded HTTP post data
	 *
	 * This is the transport format specified by the Protx VSP Direct
	 * Integration Guide.
	 *
	 * @return string the fields of this request as urlencoded HTTP post data.
	 */
	private function getPostFields()
	{
		$post_data = '';
		foreach ($this->data as $name => $value) {
			$post_data.= sprintf('&%s=%s', $name, urlencode($value));
		}

		// remove leading ampersand
		$post_data = substr($post_data, 1);

		return $post_data;
	}

	// }}}
	// {{{ private function parseErrorPage()

	/**
	 * Parses a Protx VSP Direct HTML error page to get the error message
	 * contents
	 *
	 * This method is used to generate helpful error messages in the event that
	 * something goes wrong.
	 *
	 * @param DOMDocument $document
	 *
	 * @return string the error message contained in the error page.
	 */
	private function parseErrorPage(DOMDocument $document)
	{
		$error = '';

		$blockquotes = $document->getElementsByTagName('blockquote');
		if ($blockquotes->length > 0) {
			$value = (string)$blockquotes->item(0)->nodeValue;
			$error = trim(preg_replace('/\s+/s', ' ', $value));
		}

		return $error;
	}

	// }}}
}

?>
