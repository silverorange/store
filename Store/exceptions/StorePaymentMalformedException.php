<?php

/**
 * Exception that is thrown when a malformed payment request is processed.
 *
 * A malformed request differs from an invalid request in that an invalid
 * request is correctly formed but contains the wrong values and a malformed
 * request is not correctly formed.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StorePaymentRequest, StorePaymentProvider,
 *            StorePaymentInvalidException
 */
class StorePaymentMalformedException extends StorePaymentException {}
