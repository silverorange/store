<?php

require_once 'Swat/SwatMessage.php';

/**
 * A data class to store a message  
 *
 * StoreMessage is an extension of {@link SwatMessage}.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreMessage extends SwatMessage
{
	// {{{ constants

	/**
	 * Cart notification message type
	 * 
	 * An informative message about the cart.
	 */
	const CART_NOTIFICATION = 100;

	// }}}
	// {{{ public function getCssClass()

	public function getCssClass()
	{
		$class = parent::getCssClass();

		switch ($this->type) {
			case StoreMessage::CART_NOTIFICATION :
				$class.= ' store-message-cart-notification';
				break;
		}

		return $class;
	}

	// }}}
	// {{{ protected function getTypes()

	/**
	 * Get valid message types
	 *
	 * @return array the valid types of a message.
	 */
	protected function getTypes()
	{
		$types = parent::getTypes();
		$types[] = self::CART_NOTIFICATION;

		return $types;
	}

	// }}}
}

?>
