<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An address for an e-commerce web application
 *
 * Addresses usually belongs to customers but can be used in other instances.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddress extends SwatDBDataObject
{
	public $country;
	public $provstate;
	public $zipcode;

	/**
	 * Loads an address from the database into this object
	 *
	 * @param integer $id the database id of the address to load.
	 *
	 * @return boolean true if the address was found in the database and false
	 *                  if the address was not found in the database.
	 */
	public function loadFromDB($id)
	{
		$fields = array_diff(array_keys($this->getProperties()), $this->db_field_blacklist);
		$values = SwatDB::queryRow($this->app->db, 'addresses', $fields, 'addressid', $id);
		$this->setValues($values);
		$this->generatePropertyHashes();
	}
}

/*
 * Implementation note:
 *  use same pattern as for customer to load addresses and then use the load
 *  methods in the customer methods
 */

