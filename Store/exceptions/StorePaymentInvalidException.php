<?php

require_once 'Store/exceptions/StoreException.php';

/**
 * Exception that is thrown when an invalid payment request is processed
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentRequest, StorePaymentProvider
 */
class StorePaymentInvalidException extends StoreException
{
}

?>
