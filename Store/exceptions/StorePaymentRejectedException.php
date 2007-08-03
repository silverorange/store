<?php

require_once 'Store/exceptions/StorePaymentException.php';

/**
 * Thrown when a payment request is rejected based on merchant configured rules
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentRequest, StorePaymentProvider
 */
class StorePaymentRejectedException extends StorePaymentException
{
}

?>
