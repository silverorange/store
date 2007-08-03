<?php

require_once 'Store/exceptions/StoreException.php';

/**
 * Base class for payment exceptions
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentRequest, StorePaymentProvider
 */
abstract class StorePaymentException extends StoreException
{
}

?>
