<?php

require_once 'Store/exceptions/StoreException.php';

/**
 * Thrown when a payment request is not authorized by the issuing bank
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentRequest, StorePaymentProvider
 */
class StorePaymentNotAuthorizedException extends StoreException
{
}

?>
