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
	 * Alternative free-form field for provstate of this address
	 *
	 * @var string
	 */
	public $provstate_other;

	/**
	 * The ZIP Code or postal code of this address
	 *
	 * @var string
	 */
	public $postal_code;

	// }}}
	// {{{ protected function init()

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
	 */
	public function display()
	{
		$address_tag = new SwatHtmlTag('address');
		$address_tag->open();

		switch ($this->country->id) {
		case 'GB':
			$this->displayGB();
			break;
		default:
			$this->displayCA();
		}

		$address_tag->close();
	}

	// }}}
	// {{{ public function displayCondensed()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display uses XHTML and is ideal for cell renderers. The format of
	 * this display borrows from but does not conform to post office address
	 * formatting rules.
	 */
	public function displayCondensed()
	{
		/*
		 * Condensed display is intentionally not wrapped in an address tag so
		 * it may be wrapped inside an inline element. See r6634.
		 */

		switch ($this->country->id) {
		case 'GB':
			$this->displayCondensedGB();
			break;
		default:
			$this->displayCondensedCA();
		}
	}

	// }}}
	// {{{ public function displayCondensedAsText()

	/**
	 * Displays this address in a two-line condensed form
	 *
	 * This display is formatted as plain text and is ideal for emails. The
	 * format of this display borrows from but does not conform to post office
	 * address formatting rules.
	 */
	public function displayCondensedAsText()
	{
		switch ($this->country->id) {
		case 'GB':
			$this->displayCondensedAsTextGB();
			break;
		default:
			$this->displayCondensedAsTextCA();
		}
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StoreAddress $address)
	{
		$this->fullname        = $address->fullname;
		$this->line1           = $address->line1;
		$this->line2           = $address->line2;
		$this->city            = $address->city;
		$this->postal_code     = $address->postal_code;
		$this->provstate_other = $address->provstate_other;
		$this->provstate       = $address->getInternalValue('provstate');
		$this->country         = $address->getInternalValue('country');
	}

	// }}}
	// {{{ protected function displayCA()

	/**
	 * Displays this address in postal format
	 *
	 * Canadian address format rules are taken from {@link Canada Post
	 * http://canadapost.ca/personal/tools/pg/manual/b03-e.asp}
	 */
	protected function displayCA()
	{
		echo SwatString::minimizeEntities($this->fullname), '<br />',
			SwatString::minimizeEntities($this->line1), '<br />';

		if (strlen($this->line2) > 0)
			echo SwatString::minimizeEntities($this->line2), '<br />';

		echo SwatString::minimizeEntities($this->city), ', ';

		if ($this->provstate !== null)
			echo SwatString::minimizeEntities($this->provstate->abbreviation);
		elseif (strlen($this->provstate_other) > 0)
			echo SwatString::minimizeEntities($this->provstate_other);

		echo '&nbsp;&nbsp;';
		echo SwatString::minimizeEntities($this->postal_code);
		echo '<br />';
		echo SwatString::minimizeEntities($this->country->title), '<br />';
	}

	// }}}
	// {{{ protected function displayGB()

	/**
	 * Displays this address in postal format
	 */
	protected function displayGB()
	{
		echo SwatString::minimizeEntities($this->fullname), '<br />',
			SwatString::minimizeEntities($this->line1), '<br />';

		if (strlen($this->line2) > 0)
			echo SwatString::minimizeEntities($this->line2), '<br />';

		echo SwatString::minimizeEntities($this->city), '<br />';

		if (strlen($this->provstate_other) > 0)
			echo SwatString::minimizeEntities($this->provstate_other), '<br />';

		echo SwatString::minimizeEntities($this->postal_code);
		echo '<br />';
		echo SwatString::minimizeEntities($this->country->title), '<br />';
	}

	// }}}
	// {{{ protected function displayCondensedCA()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedCA()
	{
		echo SwatString::minimizeEntities($this->fullname), ', ';
		echo SwatString::minimizeEntities($this->line1);
		if (strlen($this->line2) > 0)
			echo ', ', SwatString::minimizeEntities($this->line2);

		echo '<br />';

		echo SwatString::minimizeEntities($this->city), ' ';

		if ($this->provstate !== null)
			echo SwatString::minimizeEntities($this->provstate->abbreviation);
		elseif (strlen($this->provstate_other) > 0)
			echo SwatString::minimizeEntities($this->provstate_other);

		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			echo SwatString::minimizeEntities($this->postal_code);
		}
		echo ', ';

		echo SwatString::minimizeEntities($this->country->title);
	}

	// }}}
	// {{{ protected function displayCondensedGB()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedGB()
	{
		echo SwatString::minimizeEntities($this->fullname), ', ';
		echo SwatString::minimizeEntities($this->line1);
		if (strlen($this->line2) > 0)
			echo ', ', SwatString::minimizeEntities($this->line2);

		echo '<br />';
		echo SwatString::minimizeEntities($this->city);

		if (strlen($this->provstate_other) > 0)
			echo ', ', SwatString::minimizeEntities($this->provstate_other);

		if ($this->postal_code !== null)
			echo ', ', SwatString::minimizeEntities($this->postal_code);

		echo ', ', SwatString::minimizeEntities($this->country->title);
	}

	// }}}
	// {{{ protected function displayCondensedAsTextCA()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextCA()
	{
		echo $this->fullname, ', ';
		echo $this->line1;
		if (strlen($this->line2) > 0)
			echo ', ', $this->line2;

		echo "\n";

		echo $this->city, ' ';

		if ($this->provstate !== null)
			echo $this->provstate->abbreviation;
		elseif (strlen($this->provstate_other) > 0)
			echo $this->provstate_other;

		if ($this->postal_code !== null) {
			echo '  ';
			echo $this->postal_code;
		}
		echo ', ';

		echo $this->country->title;
	}

	// }}}
	// {{{ protected function displayCondensedAsTextGB()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextGB()
	{
		echo $this->fullname, ', ';
		echo $this->line1;
		if (strlen($this->line2) > 0)
			echo ', ', $this->line2;

		echo "\n";

		echo $this->city;
		if (strlen($this->provstate_other) > 0)
			echo ', ', $this->provstate_other;

		if ($this->postal_code !== null)
			echo ', ', $this->postal_code;

		echo ', ', $this->country->title;
	}

	// }}}
}

?>
