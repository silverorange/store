<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/StoreCustomer.php';

/**
 * A wrapper class for loading StoreCustomer objects from the database
 *
 * If there are many StoreCustomer objects that must be loaded for a page
 * request, this class should be used to load the objects from a single query.
 *
 * The typical usage of this object is:
 *
 * <code>
 * $sql = 'select a bunch of customers';
 * $customers = $db->query($sql, null, true, 'StoreItemCustomer');
 * foreach ($customers as $customer) {
 *     // do something with each customer object here ...
 * }
 * </code>
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCustomer
 */
class StoreCustomerWrapper extends SwatDBRecordsetWrapper
{
	/**
	 * Creates a new wrapper object
	 *
	 * The constructor takes the result set of a MDB2 query and translates
	 * it into an array of objects.
	 *
	 * @param mixed $rs the record set of the MDB2 query to get the customers.
	 *
	 * @access public
	 */
	public function __construct($rs)
	{
		$this->row_wrapper_class = 'StoreCustomer';
		parent::__constuct($rs);
	}
}

?>
