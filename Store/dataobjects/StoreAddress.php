<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreCountry.php';
require_once 'Store/dataobjects/StoreProvState.php';

/**
 * An address for an e-commerce web application
 *
 * Addresses usually belongs to accounts but may be used in other instances.
 * There is intentionally no reference back to the account or order this
 * address belongs to.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountAddress, StoreOrderAddress
 */
abstract class StoreAddress extends StoreDataObject
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

		$this->registerInternalProperty('provstate',
			$this->class_map->resolveClass('StoreProvState'));

		$this->registerInternalProperty('country',
			$this->class_map->resolveClass('StoreCountry'));

		$this->registerDateProperty('createdate');
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
		$address_tag = new SwatHtmlTag('address');
		$address_tag->open();

		echo SwatString::minimizeEntities($this->fullname);
		echo '<br />';

		echo SwatString::minimizeEntities($this->line1);
		echo '<br />';

		if ($this->line2 !== null) {
			echo SwatString::minimizeEntities($this->line2);
			echo '<br />';
		}

		echo SwatString::minimizeEntities($this->city);
		echo SwatString::minimizeEntities($this->provstate->abbreviation);
		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			echo SwatString::minimizeEntities($this->postal_code);
		}
		echo '<br />';

		echo SwatString::minimizeEntities($this->country->title);
		echo '<br />';

		$address_tag->close();
	}

	// }}}
	// {{{ public function displayCondensed()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display uses XHTML and is ideal for cell renderers. The format of
	 * this display borrows from but does not conform to the Canada Post
	 * address rules.
	 */
	public function displayCondensed()
	{
		echo SwatString::minimizeEntities($this->fullname), ', ';
		echo SwatString::minimizeEntities($this->line1);
		if ($this->line2 !== null)
			echo ', ', SwatString::minimizeEntities($this->line2);

		echo '<br />';

		echo SwatString::minimizeEntities($this->city), ' ';
		echo SwatString::minimizeEntities($this->provstate->abbreviation);

		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			echo SwatString::minimizeEntities($this->postal_code);
		}
		echo ', ';

		echo SwatString::minimizeEntities($this->country->title);
	}

	// }}}
	// {{{ public function displayCondensedAsText()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display is formatted as plain text and is ideal for emails. The
	 * format of this display borrows from but does not conform to the Canada
	 * Post address rules.
	 */
	public function displayCondensedAsText()
	{
		echo $this->fullname, ', ';
		echo $this->line1;
		if ($this->line2 !== null)
			echo ', ', $this->line2;

		echo "\n";

		echo $this->city, ' ';
		echo $this->provstate->abbreviation;

		if ($this->postal_code !== null) {
			echo '  ';
			echo $this->postal_code;
		}
		echo ', ';

		echo $this->country->title;
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StoreAddress $address)
	{
		$this->fullname    = $address->fullname;
		$this->line1       = $address->line1;
		$this->line2       = $address->line2;
		$this->city        = $address->city;
		$this->postal_code = $address->postal_code;
		$this->provstate   = $address->getInternalValue('provstate');
		$this->country     = $address->getInternalValue('country');
	}

	// }}}
}

?>
