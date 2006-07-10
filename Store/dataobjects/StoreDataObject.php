<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/StoreClassMap.php';

/**
 * A data-object that contains a class-mapping object
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreDataObject extends SwatDBDataObject
{
	// {{{ protected properties

	/**
	 * The class-mapping object
	 *
	 * @var StoreClassMap
	 */
	protected $class_map;

	// }}}
	// {{{ public function __construct(

	public function __construct($data = null)
	{
		$this->class_map = StoreClassMap::instance();
		parent::__construct($data);
	}

	// }}}
	// {{{ public function __wakeup()

	public function __wakeup()
	{
		$this->class_map = StoreClassMap::instance();
		parent::__wakeup();
	}

	// }}}
}

?>
