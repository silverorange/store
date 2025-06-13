<?php

/**
 * @copyright 2018 silverorange
 */
class StorePaymentBraintreeGatewayException extends StorePaymentBraintreeException
{
    protected $reason;

    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    public function getReason()
    {
        return $this->reason;
    }
}
