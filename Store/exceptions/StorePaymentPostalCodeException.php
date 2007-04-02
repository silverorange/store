<?php

require_once 'Store/exceptions/StorePaymentException.php';

/**
 * Exception that is thrown for postal code AVS checks
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider
 */
class StorePaymentPostalCodeException extends StorePaymentException
{
}

?>
