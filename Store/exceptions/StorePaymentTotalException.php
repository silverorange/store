<?php

require_once 'Store/exceptions/StorePaymentException.php';

/**
 * Exception that is thrown when an order total is too high for processing
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider
 */
class StorePaymentTotalException extends StorePaymentException
{
}

?>
