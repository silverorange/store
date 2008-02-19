<?php

require_once 'Store/exceptions/StorePaymentException.php';

/**
 * Exception that is thrown when PayPal is requested but is not available
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentPayPalUnavailableException extends StorePaymentException
{
}

?>
