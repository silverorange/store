<?php

require_once 'Store/exceptions/StorePaymentException.php';

/**
 * Exception that is thrown when 3-D Secure is not supported for a payment
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider
 */
class StorePayment3DSecureUnsupportedException extends StorePaymentException
{
}

?>
