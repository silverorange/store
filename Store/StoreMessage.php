<?php

require_once 'Swat/SwatMessage.php';

/**
 * A data class to store a message
 *
 * @package    Store
 * @copyright  2006-2008 silverorange
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @deprecated Use a {@link SwatMessage} with the type 'cart' for cart
 *             messages.
 */
class StoreMessage extends SwatMessage
{
	// {{{ constants

	/**
	 * Cart notification message type
	 *
	 * An informative message about the cart.
	 *
	 * @deprecated Use the string 'cart' instead.
	 */
	const CART_NOTIFICATION = 'cart';

	// }}}
	// {{{ public function getCSSClassString()

	public function getCSSClassString()
	{
		$class = parent::getCSSClassString();

		// legacy
		switch ($this->type) {
		case StoreMessage::CART_NOTIFICATION :
			$class.= ' store-message-cart-notification';
			break;
		}

		return $class;
	}

	// }}}
}

?>
