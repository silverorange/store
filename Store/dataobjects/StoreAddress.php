<?php

require_once 'Store/StoreCountry.php';
require_once 'Store/StoreProvState.php';

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An address for an e-commerce web application
 *
 * Addresses usually belongs to customers but can be used in other instances.
 * There is intentionally no reference back to the account or order this
 * address belongs to.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddress extends SwatDBDataObject
{
	/**
	 * The full name of the address holder
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * Line 1 of the address
	 *
	 * This usually corresponds to the street name and number.
	 *
	 * @var string
	 */
	public $line1;

	/**
	 * Optional line 2 of the address
	 *
	 * This usually corresponds to a suite or apartment number.
	 *
	 * @var string
	 */
	public $line2;

	/**
	 * The city of this address
	 *
	 * @var string
	 */
	public $city;

	/**
	 * The ZIP Code or postal code of this address
	 *
	 * @var string
	 */
	public $postalcode;

	/**
	 * The date this address was created
	 *
	 * This field is useful for ordering multiple addresses.
	 *
	 * @var SwatDate
	 */
	public $createdate;

	public function init()
	{
		$this->registerInternalField('provstate', 'StoreProvState');
		$this->registerInternalField('country', 'StoreCountry');
		$this->registerDateField('createdate');
	}

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

	public function display()
	{
		$br_tag = new SwatHtmlTag('br');
		$address_tag = new SwatHtmlTag('address');
		$address_tag->open();

		echo SwatString::minimizeEntities($this->fullname);
		$br_tag->display();

		echo SwatString::minimizeEntities($this->line1);
		$br_tag->display();

		if ($this->line2 !== null) {
			echo SwatString::minimizeEntities($this->line2);
			$br_tag->display();
		}

		echo SwatString::minimizeEntities($this->city);
		$br_tag->display();

		echo SwatString::minimizeEntities($this->provstate->title);
		$br_tag->display();

		echo SwatString::minimizeEntities($this->country->title);
		$br_tag->display();

		if ($this->postalcode !== null) {
			echo SwatString::minimizeEntities($this->postalcode);
			$br_tag->display();
		}

		$address_tag->close();
	}

	public function displayCondensed()
	{
	}
}

?>
