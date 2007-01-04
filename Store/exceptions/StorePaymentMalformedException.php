<?php

require_once 'Store/exceptions/StorePaymentException.php';

/**
 * Exception that is thrown when a malformed payment request is processed
 *
 * A malformed request differs from an invalid request in that an invalid
 * request is correctly formed but contains the wrong values and a malformed
 * request is not correctly formed.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentRequest, StorePaymentProvider,
 *            StorePaymentInvalidException
 */
class StorePaymentMalformedException extends StorePaymentException
{
}

?>
