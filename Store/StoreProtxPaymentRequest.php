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
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      TYPE_VERIFY and TYPE_VERIFIEDPAY are not implemented.
 */
class StoreProtxPaymentRequest extends StorePaymentRequest
{
	// {{{ class constants

	/**
	 * URL for processing simulator mode payment transactions
	 */
	const URL_SIMULATOR_PAYMENT =
		'https://ukvpstest.protx.com/VSPSimulator/VSPDirectGateway.asp';

	/**
	 * URL for processing test mode payment transactions
	 */
	const URL_TEST_PAYMENT =
		'https://ukvpstest.protx.com/vspgateway/service/vspdirect-register.vsp';

	/**
	 * URL for processing live mode payment transactions
	 */
	const URL_LIVE_PAYMENT =
		'https://ukvps.protx.com/vspgateway/service/vspdirect-register.vsp';

	/**
	 * URL for processing simulator mode transactions other than payment
	 * transactions
	 */
	const URL_SIMULATOR =
		'https://ukvpstest.protx.com/VSPSimulator/VSPServerGateway.asp?Service=Vendor%sTx';

	/**
	 * URL for processing test mode transactions other than payment
	 * transactions
	 */
	const URL_TEST =
		'https://ukvpstest.protx.com/vspgateway/service/%s.vsp';

	/**
	 * URL for processing live mode transactions other than payment
	 * transactions
	 */
	const URL_LIVE =
		'https://ukvps.protx.com/vspgateway/service/%s.vsp';

	/**
	 * URL for getting live transaction status
	 *
	 * No such feature exists for the test or simulator modes.
	 */
	const URL_LIVE_STATUS = 'https://ukvps.protx.com/txstatus/txstatus.asp';

	/**
	 * URL for processing simulator mode 3-DS authentication transactions
	 */
	const URL_SIMULATOR_3DS_AUTH =
		'https://ukvpstest.protx.com/VSPSimulator/VSPDirectCallback.asp';

	/**
	 * URL for processing test mode 3-DS authentication transactions
	 */
	const URL_TEST_3DS_AUTH =
		'https://ukvpstest.protx.com/vspgateway/service/direct3dcallback.vsp';

	/**
	 * URL for processing live mode 3-DS authentication transactions
	 */
	const URL_LIVE_3DS_AUTH =
		'https://ukvps.protx.com/vspgateway/service/direct3dcallback.vsp';

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

		$this->url = $this->getUrl($this->mode, $type);

		if ($type != StorePaymentRequest::TYPE_STATUS &&
			$type != StorePaymentRequest::TYPE_3DS_AUTH) {

			$type_map = $this->getTypeMap();
			$tx_type = $type_map[$type];

	 		// For Protx VSP Direct, the protocol version is defaulted to
			// '2.22'.
			$this->setField('VPSProtocol', '2.22');
			$this->setField('TxType', $tx_type);
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
		case StorePaymentRequest::TYPE_3DS_AUTH:
			$this->makeFieldsRequired($this->get3dsAuthRequiredFields());
			break;
		}

		if ($this->url !== null) {
			$this->curl_handle = curl_init();
			curl_setopt_array($this->curl_handle, array(
				CURLOPT_URL  => $this->url,
				CURLOPT_POST => 1,
				CURLOPT_RETURNTRANSFER => 1,
				));
		}
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

		if ($this->curl_handle !== null) {
			curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS,
				$this->getPostFields());

			$response_text = curl_exec($this->curl_handle);
			$response_code = curl_getinfo($this->curl_handle,
				CURLINFO_HTTP_CODE);

			// check for HTTP OK
			if ($response_code != 200) {
				throw new StoreException(sprintf(
					"Request was not successful. HTTP code is: %s\n".
					"Response text is:\n%s",
					$response_code, $response_text));
			}

			$response = new StoreProtxPaymentResponse($response_text);
		} else {
			if ($this->type == StorePaymentRequest::TYPE_STATUS)
				$response = new StoreProtxPaymentResponse(
					$this->getFakeStatusResponseText());
			else
				$response = new StoreProtxPaymentResponse('');
		}

		return $response;
	}

	// }}}
	// {{{ public function __destruct()

	/**
	 * Cleans up resources used by this request
	 */
	public function __destruct()
	{
		if ($this->curl_handle !== null)
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
			StorePaymentRequest::TYPE_VERIFY        => 'AUTHENTICATE',
			StorePaymentRequest::TYPE_REFUND        => 'REFUND',
			StorePaymentRequest::TYPE_VERIFIEDPAY   => 'AUTHORISE',
			StorePaymentRequest::TYPE_VOID          => 'VOID',
			StorePaymentRequest::TYPE_HOLD          => 'DEFERRED',
			StorePaymentRequest::TYPE_RELEASE       => 'RELEASE',
			StorePaymentRequest::TYPE_ABORT         => 'ABORT',
			// no protocol-specific type exists for status or 3-DS auth
			StorePaymentRequest::TYPE_STATUS        => '',
			StorePaymentRequest::TYPE_3DS_AUTH      => '',
		);

		return $type_map;
	}

	// }}}
	// {{{ protected function getDefaultRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required by default
	 *
	 * No fields are required since the request types are so disparate. See
	 * the VSP Direct Integration Guidelines document for details.
	 *
	 * @return array an empty array.
	 */
	protected function getDefaultRequiredFields()
	{
		return array();
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
			'Vendor',
			'VendorTxCode',
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
			'Vendor',
			'VendorTxCode',
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
			'Vendor',
			'VendorTxCode',
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
			'Vendor',
			'VendorTxCode',
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
			'Vendor',
			'VendorTxCode',
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
		return array(
			'Vendor',
			'VendorTxCode',
		);
	}

	// }}}
	// {{{ protected function get3dsAuthRequiredFields()

	/**
	 * Gets a list of protocol-specific fields that are required for a 3-DS
	 * authentication request
	 *
	 * See Appendix 3 in the VSP Direct Protocol and Integration Guidelines.
	 *
	 * @return array a list of protocol-specific fields that are required for
	 *                a 3-DS authentication request.
	 */
	protected function get3dsAuthRequiredFields()
	{
		return array(
			'MD',
			'PARes',
		);
	}

	// }}}
	// {{{ private function getUrl()

	private function getUrl($mode, $type)
	{
		$url = null;

		switch ($mode) {
		case 'simulator':
			switch ($type) {
				case StorePaymentRequest::TYPE_VERIFY:
				case StorePaymentRequest::TYPE_HOLD:
				case StorePaymentRequest::TYPE_PAY:
					$url = self::URL_SIMULATOR_PAYMENT;
					break;

				case StorePaymentRequest::TYPE_STATUS:
					break;

				case StorePaymentRequest::TYPE_3DS_AUTH:
					$url = self::URL_SIMULATOR_3DS_AUTH;
					break;

				default:
					$type_map = $this->getTypeMap();
					$tx_type = $type_map[$type];
					$url = sprintf(self::URL_SIMULATOR,
						ucfirst(strtolower($tx_type)));

					break;
			}
			break;

		case 'test':
			switch ($type) {
				case StorePaymentRequest::TYPE_VERIFY:
				case StorePaymentRequest::TYPE_HOLD:
				case StorePaymentRequest::TYPE_PAY:
					$url = self::URL_TEST_PAYMENT;
					break;

				case StorePaymentRequest::TYPE_STATUS:
					break;

				case StorePaymentRequest::TYPE_3DS_AUTH:
					$url = self::URL_TEST_3DS_AUTH;
					break;

				default:
					$type_map = $this->getTypeMap();
					$tx_type = $type_map[$type];
					$url = sprintf(self::URL_TEST, strtolower($tx_type));

					break;
			}
			break;

		case 'live':
			switch ($type) {
				case StorePaymentRequest::TYPE_VERIFY:
				case StorePaymentRequest::TYPE_HOLD:
				case StorePaymentRequest::TYPE_PAY:
					$url = self::URL_LIVE_PAYMENT;
					break;

				case StorePaymentRequest::TYPE_STATUS:
					$url = self::URL_LIVE_STATUS;
					break;

				case StorePaymentRequest::TYPE_3DS_AUTH:
					$url = self::URL_LIVE_3DS_AUTH;
					break;

				default:
					$type_map = $this->getTypeMap();
					$tx_type = $type_map[$type];
					$url = sprintf(self::URL_LIVE, strtolower($tx_type));

					break;
			}
			break;
		}

		return $url;
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
	// {{{ private function getFakeStatusResponseText()

	/**
	 * Gets the text of a fake status response which is used when a status
	 * request is performed in 'test' or 'simulator' mode
	 *
	 * Protx does not have a test server for status requests. This method
	 * provides a dogfood response that matches the format of a real response
	 * from the live server.
	 *
	 * @return string a string containing the fake status request response text.
	 */
	private function getFakeStatusResponseText()
	{
		return
			"Status=OK\r\n".
			"StatusDetail=Fake status response for testing.\r\n".
			"TransactionType=DEFERRED\r\n".
			"Released=NO\r\n".
			"VSPTxID={1234-5678-9012-3456-7890-123456789}\r\n".
			"Amount=314.16\r\n".
			"Currency=USD\r\n".
			"Received=14/03/07 03:14:16\r\n".
			"Settled=NO\r\n";

	}

	// }}}
}

?>
