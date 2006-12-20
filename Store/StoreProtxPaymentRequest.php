<?php

require_once 'Store/StorePaymentRequest.php';
require_once 'Store/StoreProtxPaymentResponse.php';

class StoreProtxPaymentRequest extends StorePaymentRequest
{
	// {{{ class constants

	const URL_SIMULATOR =
		'https://ukvpstest.protx.com/VSPSimulator/VSPDirectGateway.asp';

	const URL_TEST_PAYMENT =
		'https://ukvpstest.protx.com/vpsDirectAuth/PaymentGateway3D.asp';

	const URL_LIVE_PAYMENT =
		'https://ukvps.protx.com/vpsDirectAuth/PaymentGateway3D.asp';

	const URL_TEST =
		'https://ukvpstest.protx.com/vps200/dotransaction.dll?Service=Vendor%sTx';

	const URL_LIVE =
		'https://ukvps.protx.com/vps200/dotransaction.dll?Service=Vendor%sTx';

	// }}}
	// {{{ public properties

	public static $default_mode = 'simulator';

	// }}}
	// {{{ private properties

	private $curl_handle;
	private $url;
	private static $type_map = null;
	private static $default_required_fields = null;

	// }}}
	// {{{ public function __construct()

	public function __construct($type = StorePaymentRequest::TYPE_NORMAL,
		$mode = null)
	{
		if ($mode === null)
			$mode = self::$default_mode;

		$this->makeFieldsRequired($this->getDefaultRequiredFields());

		parent::__construct($type, $mode);

		$type_map = $this->getTypeMap();
		$tx_type = $type_map[$type];
		$this->setField('TxType', $tx_type);

		switch ($this->mode) {
		case 'simulator':
			$this->url = self::URL_SIMULATOR; 
			break;
		case 'test':
			$this->url = ($type === 'payment') ? self::URL_TEST_PAYMENT :
				sprintf(self::URL_TEST, ucfirst(strtolower($tx_type)));

			break;
		case 'live':
			$this->url = ($type === 'payment') ? self::URL_LIVE_PAYMENT :
				sprintf(self::URL_LIVE, ucfirst(strtolower($tx_type)));

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
		$content_type = curl_getinfo($this->curl_handle,
			CURLINFO_CONTENT_TYPE);

		// we got an error page
		if (strtolower($content_type) === 'text/html')
			throw new Exception(sprintf('Received an error page as a '.
				"response. Error contents are: '%s'.",
				$this->parseErrorPage($response_text)));

		return new StoreProtxPaymentResponse($response_text);
	}

	// }}}
	// {{{ public function __destruct()

	public function __destruct()
	{
		curl_close($this->curl_handle);
	}

	// }}}
	// {{{ protected function __toString()

	protected function __toString()
	{
		$string = sprintf("Request URL: %s\n\n", $this->url);
		foreach ($this->data as $name => $value)
			$string.= sprintf("%s=%s\n", $name, $value);

		return $string;
	}

	// }}}
	// {{{ protected function getAvailableModes()

	protected function getAvailableModes()
	{
		$modes = parent::getAvailableModes();
		$modes[] = 'simulator';
		return $modes;
	}

	// }}}
	// {{{ protected function getTypeMap()

	protected function getTypeMap()
	{
		static $type_map = array(
			StorePaymentRequest::TYPE_NORMAL   => 'PAYMENT',
			StorePaymentRequest::TYPE_AUTH     => 'PREAUTH',
			StorePaymentRequest::TYPE_CREDIT   => 'CREDIT',
			StorePaymentRequest::TYPE_POSTAUTH => 'REPEAT',
			StorePaymentRequest::TYPE_VOID     => 'VOID',
			StorePaymentRequest::TYPE_DEFERRED => 'DEFERRED',
			StorePaymentRequest::TYPE_RELEASE  => 'RELEASE',
		);

		return $type_map;
	}

	// }}}
	// {{{ protected function getDefaultRequiredFields()

	protected function getDefaultRequiredFields()
	{
		static $default_required_fields = array(
			'VPSProtocol',
			'TxType',
			'Vendor',
			'VendorTxCode',
			'Amount',
			'Currency',
			'Description',
			'CardHolder',
			'CardNumber',
			'ExpiryDate',
			'CardType',
		);

		return $default_required_fields;
	}

	// }}}
	// {{{ protected function getDefaultData()

	protected function getDefaultData()
	{
		static $default_data = array(
			'VPSProtocol'  => '2.22',
		);

		return $default_data;
	}

	// }}}
	// {{{ private function getPostFields()

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

	private function parseErrorPage($html_content)
	{
		$error = '';

		set_error_handler(create_function('$errno, $errstr', ''),
			E_NOTICE | E_WARNING);

		$document = new DOMDocument();
		$document->loadHTML($html_content);

		restore_error_handler();

		$blockquotes = $document->getElementsByTagName('blockquote');
		if (count($blockquotes) > 0) {
			$value = (string)$blockquotes->item(0)->nodeValue;
			$error = trim(preg_replace('/\s+/s', ' ', $value));
		}

		return $error;
	}

	// }}}
}

?>
