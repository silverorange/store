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
	/**
	 * The country code of this address
	 *
	 * Examples: CA, US, GB
	 *
	 * @var string
	 */
	public $country;

	/**
	 * The province or state of this address
	 *
	 * @var string
	 */
	public $provstate;

	/**
	 * The zip or postal code of this address
	 *
	 * @var string
	 */
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
		$fields = array_diff(array_keys($this->getProperties()),
			$this->db_field_blacklist);
		
		$values = SwatDB::queryRow($this->app->db, 'addresses',
			$fields, 'addressid', $id);
		
		$this->setValues($values);
		$this->generatePropertyHashes();
	}

	public function saveToDB()
	{
	}
}

?>
