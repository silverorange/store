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
	
	/**
	 * 
	 *
	 * @param MDB2_Driver $db
	 * @param string $postal_code
	 * @param integer $provstate
	 *
	 * @return boolean
	 */
	public static function validatePostalCode($db, $postal_code, $provstate)
	{
		$sql = sprintf('select country, pcode from provstates where id = %s',
			$db->quote($provstate, 'integer'));

		$row = SwatDB::queryRow($db, $sql);

		if (strpos($postal_code, $row->pcode) !== 0)
			return false;

		switch ($row->country) {
		case 'CA':
			// translate commonly mis-written letters and numbers
			$regexp = '/^[a-z]\d[a-z](\s)?\d[a-z]\d$/ui';
			break;
		case 'US':
			$regexp = '/^\d{5}[- 0-9]*$/ui';
			break;
		}

		return true;
	}
}

?>
