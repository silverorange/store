<?php

require_once 'Swat/SwatMessageDisplay.php';
require_once 'Store/StoreMessage.php';

/**
 * A control to display page status messages  
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreMessageDisplay extends SwatMessageDisplay
{
	// {{{ public function __construct()
 
	/**
	 * Creates a new message display
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet('packages/store/styles/store-message.css',
			Store::PACKAGE_ID);
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
