<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/StoreItem.php';

/**
 * A wrapper class for loading StoreItem objects from the database
 *
 * If there are many StoreItem objects that must be loaded for a page request,
 * this class should be used to load the objects from a single query.
 *
 * The typical usage of this object is:
 *
 * <code>
 * $sql = 'select a bunch of items';
 * $items = $db->query($sql, null, true, 'StoreItemWrapper');
 * foreach ($items as $item) {
 *     // do something with each item object here ...
 * }
 * </code>
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItem
 */
class StoreItemWrapper extends SwatDBRecordsetWrapper
{
	/**
	 * Creates a new wrapper object
	 *
	 * The constructor takes the result set of a MDB2 query and translates
	 * it into an array of objects.
	 *
	 * @param resource $rs the record set of the MDB2 query to get the items.
	 *
	 * @access public
	 */
	public function __construct($rs)
	{
		$this->row_wrapper_class = 'StoreItem';
		parent::__constuct($rs);
	}
}

?>
