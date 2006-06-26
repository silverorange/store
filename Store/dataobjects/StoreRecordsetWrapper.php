<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/StoreClassMap.php';

/**
 * A recordset wrapper that includes a class-mapping object
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreRecordsetWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected properties

	/**
	 * The class-mapping object
	 *
	 * @var StoreClassMap
	 */
	protected $class_map;

	// }}}
	// {{{ public function __construct()

	public function __construct($rs)
	{
		$this->class_map = StoreClassMap::instance();
		parent::__construct($rs);
	}

	// }}}
}

?>
