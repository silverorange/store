<?php

require_once 'Store/StorePaymentRequest.php';

class StorePayflowProPaymentRequest extends StorePaymentRequest
{
	// {{{ class constants

	const SERVER_TEST = 'test.signio.com';
	const SERVER_LIVE = 'connect.signio.com';

	// }}}
	// {{{ public properties

	public static $default_mode = 'test';

	// }}}
	// {{{ public function __construct()

	public function __construct($type = StorePaymentRequest::TYPE_NORMAL,
		$mode = null)
	{
		if ($mode === null)
			$mode = self::$default_mode;

		parent::__construct($type, $mode);

		$type_map = $this->getTypeMap();
		$tx_type = $type_map[$type];
		$this->setField('TRXTYPE', $tx_type);

		switch ($mode) {
		case 'test':
			$this->server = self::SERVER_TEST;
			break;
		case 'live':
			$this->server = self::SERVER_LIVE;
			break;
		}
	}

	// }}}
	// {{{ public function process

	public function process()
	{
		// TODO: add required fields as per the spec here
		if (!function_exists('pfpro_process'))
			throw new Exception('PayfloPro extension is missing. Please '.
				'install the PayflowPro extension to make PayfloPro '.
				'transactions.');

		pfpro_process($this->data, $this->server);
	}

	// }}}
	// {{{ protected function __toString()

	protected function __toString()
	{
		$string = sprintf("Request Server: %s\n\n", $this->server);
		foreach ($this->data as $name => $value)
			$string.= sprintf("%s=%s\n", $name, $value);

		return $string;
	}

	// }}}
	// {{{ protected function getTypeMap()

	protected function getTypeMap()
	{
		static $type_map = array(
			StorePaymentRequest::TYPE_NORMAL   => 'S',
			StorePaymentRequest::TYPE_AUTH     => 'A',
			StorePaymentRequest::TYPE_CREDIT   => 'C',
			StorePaymentRequest::TYPE_POSTAUTH => 'D',
			StorePaymentRequest::TYPE_VOID     => 'V',
		);

		return $type_map;
	}

	// }}}
	// {{{ protected function getDefaultRequiredFields()

	protected function getDefaultRequiredFields()
	{
		static $default_required_fields = array(
		// TODO: add payflo pro required fields here
		);

		return $default_required_fields;
	}

	// }}}
	// {{{ protected function getDefaultData()

	protected function getDefaultData()
	{
		static $default_data = array(
			'PARTNER' => 'PayPal',
		);

		return $default_data;
	}

	// }}}
}

?>
