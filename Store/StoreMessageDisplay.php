<?php

require_once 'Swat/SwatMessageDisplay.php';
require_once 'Store/StoreMessage.php';

/**
 * A control to display page status messages
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreMessageDisplay extends SwatMessageDisplay
{
	// {{{ public function display()

	public function display(SwatDisplayContext $context)
	{
		if (!$this->visible) {
			return;
		}

		parent::display($context);

		$context->addStyleSheet('packages/store/styles/store-message.css');
	}

	// }}}
	// {{{ protected function getDismissableMessageTypes()

	/**
	 * Gets an array of message types that are dismissable by default
	 *
	 * StoreMessageDisplay adds the {@link StoreMessage::CART_NOTIFICATION}
	 * type to the default list.
	 *
	 * @return array message types that are dismissable by default.
	 */
	protected function getDismissableMessageTypes()
	{
		$types = parent::getDismissableMessageTypes();
		$types[] = StoreMessage::CART_NOTIFICATION;
		return $types;
	}

	// }}}
}

?>
