<?php

require_once 'Swat/SwatObject.php';

/**
 * Abstract base for a class that deletes private data
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StorePrivateDataDeleter extends SwatObject
{
	// {{{ public properties

	/**
	 * A reference to the application
	 *
	 * @var StorePrivateDataDeleterApplication
	 */
	public $app;

	// }}}
	// {{{ abstract public function run()

	abstract public function run();

	// }}}
}

?>
