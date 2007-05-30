<?php

require_once 'Swat/SwatUI.php';

/**
 * UI manager for the store package
 *
 * Subclass of {@link SwatUI} for use with the Store package.  This can be used
 * as a central place to add {@link SwatUI::$class_map class maps} and 
 * {@link SwatUI::registerHandler() UI handlers} that are specific to the Store
 * package.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreUI extends SwatUI
{
	// {{{ public function __construct()

	/**
	 * Creates a new StoreUI object
	 */
	public function __construct()
	{
		parent::__construct();

		$this->class_map['Store'] = 'Store';
		$this->class_map['Site'] = 'Site';
	}

	// }}}
}

?>
