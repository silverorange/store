<?php

/**
 * Thrown when a payment request is not authorized by the issuing bank.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StorePaymentRequest, StorePaymentProvider
 */
class StorePaymentNotAuthorizedException extends StorePaymentException {}
