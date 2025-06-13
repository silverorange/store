<?php

/**
 * @copyright 2018 silverorange
 */
class StorePaymentBraintreeGatewayException extends StorePaymentBraintreeException
{
    // {{{ protected properties

    protected $reason;

    // }}}
    // {{{ public function setReason()

    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    // }}}
    // {{{ public function getReason()

    public function getReason()
    {
        return $this->reason;
    }

    // }}}
}
