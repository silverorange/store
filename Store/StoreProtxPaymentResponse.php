<?php

require_once 'StorePaymentResponse.php';

class StoreProtxPaymentResponse extends StorePaymentResponse
{
	private $response = array();
	private $response_text;

	public function __construct($response_text)
	{
		$this->parseResponse($response_text);
		$this->response_text = str_replace("\r\n", "\n", $response_text);
	}

	public function getField($name)
	{
		if (isset($this->response[$name]))
			return $this->response[$name];
	}

	public function hasField($name)
	{
		return (isset($this->response[$name]));
	}

	protected function __toString()
	{
		return $this->response_text;
	}

	private function parseResponse($response_text)
	{
		$lines = explode("\r\n", $response_text);
		foreach ($lines as $line) {
			list($name, $value) = explode('=', $line, 2);
			$this->response[$name] = $value;
		}
	}
}

?>
