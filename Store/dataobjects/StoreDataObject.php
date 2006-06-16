<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/StoreDataObjectClassMap.php';

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
	 * @var StoreDataObjectClassMap
	 */
	protected $class_map;

	// }}}
	// {{{ public function __construct(

	public function __construct($data = null)
	{
		$this->class_map = StoreDataObjectClassMap::instance();
		parent::__construct($data);
	}

	// }}}
	// {{{ public function __wakeup()

	public function __wakeup()
	{
		parent::__wakeup();
		$this->class_map = StoreDataObjectClassMap::instance();
	}

	// }}}
}

?>
