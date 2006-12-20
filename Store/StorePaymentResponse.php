<?php

abstract class StorePaymentResponse
{
	abstract public function __construct($response_data);
	abstract public function getField($name);
	abstract public function hasField($name);
	abstract protected function __toString();
}

?>
