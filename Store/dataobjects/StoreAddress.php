<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreCountry.php';
require_once 'Store/dataobjects/StoreProvState.php';

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';

require_once 'SwatDB/SwatDB.php';

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
class StoreAddress extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Address identifier
	 *
	 * @var integer
	 */
	public $id;

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
	public $postal_code;

	// }}}
	// {{{ protection function init()

	protected function init()
	{
		$this->id_field = 'integer:id';

		$this->registerInternalField('provstate',
			$this->class_map->resolveClass('StoreProvState'));

		$this->registerInternalField('country',
			$this->class_map->resolveClass('StoreCountry'));

		$this->registerDateField('createdate');
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this address in postal format
	 *
	 * Address format rules are taken from {@link Canada Post
	 * http://canadapost.ca/personal/tools/pg/manual/b03-e.asp}
	 */
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
		echo SwatString::minimizeEntities($this->provstate->abbreviation);
		if ($this->postalcode !== null) {
			echo '&nbsp;&nbsp;';
			echo SwatString::minimizeEntities($this->postalcode);
		}
		$br_tag->display();

		echo SwatString::minimizeEntities($this->country->title);
		$br_tag->display();

		$address_tag->close();
	}

	// }}}
	// {{{ public function displayCondensed()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display uses XHTML and is ideal for cell renderers.
	 */
	public function displayCondensed()
	{
		$br_tag = new SwatHtmlTag('br');
		$address_tag = new SwatHtmlTag('address');
		$address_tag->open();

		echo SwatString::minimizeEntities($this->fullname), ', ';
		echo SwatString::minimizeEntities($this->line1);
		if ($this->line2 !== null)
			echo ', ', SwatString::minimizeEntities($this->line2);

		$br_tag->display();

		echo SwatString::minimizeEntities($this->city), ' ';
		echo SwatString::minimizeEntities($this->provstate->abbreviation);

		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			echo SwatString::minimizeEntities($this->postal_code);
		}
		echo ', ';

		echo SwatString::minimizeEntities($this->country->title);

		$address_tag->close();
	}

	// }}}
	// {{{ public function displayCondensedAsText()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display is formatted as plain text and is ideal for emails.
	 */
	public function displayCondensedAsText()
	{
		echo SwatString::minimizeEntities($this->fullname), ', ';
		echo SwatString::minimizeEntities($this->line1);
		if ($this->line2 !== null)
			echo ', ', SwatString::minimizeEntities($this->line2);

		echo "\n";

		echo SwatString::minimizeEntities($this->city), ', ';
		echo SwatString::minimizeEntities($this->provstate->abbreviation);

		if ($this->postal_code !== null) {
			echo ', ';
			echo SwatString::minimizeEntities($this->postal_code);
		}
		echo ', ';

		echo SwatString::minimizeEntities($this->country->title);
	}

	// }}}
}

?>
