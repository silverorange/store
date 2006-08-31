<?php

require_once 'Swat/SwatMessageDisplay.php';

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
}

?>
