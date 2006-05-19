<?php

require_once 'Store/exceptions/StoreException.php';

/**
 * Thrown when something is not found
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreNotFoundException extends StoreException
{
	/**
	 * Creates a new not found exception
	 *
	 * @param string $message the message of the exception.
	 * @param integer $code the code of the exception.
	 */
	public function __construct($message = null, $code = 0)
	{
		parent::__construct($message, $code);
		$this->title = _('Not Found');
		$this->http_status_code = 404;
	}
}

?>
