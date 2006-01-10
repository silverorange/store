<?php

require_once 'Swat/SwatUI.php';

/**
 * UI manager for stores
 *
 * Subclass of {@link SwatUI} for use with the Store package.  This can be used
 * as a central place to add {@link SwatUI::$classmap class maps} and 
 * {@link SwatUI::registerHandler() UI handlers} that are specific to the Store
 * package.
 *
 * @package Store
 * @copyright silverorange 2006
 */
class StoreUI extends SwatUI
{
	/**
	 * Creates a new StoreUI object
	 */
	public function __construct()
	{
		parent::__construct();

		$this->class_map['Store'] = 'Store';
	}
}

?>
