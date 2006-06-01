<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/StoreDataObjectClassMap.php';

/**
 * A recordset wrapper that includes a class-mapping object
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreRecordsetWrapper extends SwatDBRecordsetWrapper
{
	/**
	 * The class-mapping object
	 *
	 * @var StoreDataObjectClassMap
	 */
	protected $class_map;

	public function __construct($rs)
	{
		$this->class_map = StoreDataObjectClassMap::instance();
		parent::__construct($rs);
	}
}

?>
