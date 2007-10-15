<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDBDataObject.php';
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
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountAddress, StoreOrderAddress
 */
abstract class StoreAddress extends SwatDBDataObject
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
	 * The company of the address
	 *
	 * @var text
	 */
	public $company;

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

	/**
	 * Phone number for this address
	 *
	 * @var text
	 */
	public $phone;

	// }}}
	// {{{ public function display()

	/**
	 * Displays this address in postal format
	 */
	public function display()
	{
		$address_tag = new SwatHtmlTag('address');
		$address_tag->class = 'vcard';
		$address_tag->open();

		switch ($this->country->id) {
		case 'CA':
			$this->displayCA();
			break;
		case 'GB':
			$this->displayGB();
			break;
		default:
			$this->displayUS();
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
		case 'CA':
			$this->displayCondensedCA();
			break;
		case 'GB':
			$this->displayCondensedGB();
			break;
		default:
			$this->displayCondensedUS();
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
		case 'CA':
			$this->displayCondensedAsTextCA();
			break;
		case 'GB':
			$this->displayCondensedAsTextGB();
			break;
		default:
			$this->displayCondensedAsTextUS();
		}
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StoreAddress $address)
	{
		$this->fullname        = $address->fullname;
		$this->company         = $address->company;
		$this->line1           = $address->line1;
		$this->line2           = $address->line2;
		$this->city            = $address->city;
		$this->postal_code     = $address->postal_code;
		$this->provstate_other = $address->provstate_other;
		$this->phone         = $address->phone;
		$this->provstate       = $address->getInternalValue('provstate');
		$this->country         = $address->getInternalValue('country');
	}

	// }}}
	// {{{ public function getFullName()

	/**
	 * Gets the full name of the person at this address
	 *
	 * Having this method allows subclasses to split the full name into an
	 * arbitrary number of fields. For example, first name and last name.
	 *
	 * @return string the full name of the person at this address.
	 */
	public function getFullName()
	{
		return $this->fullname;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('provstate',
			SwatDBClassMap::get('StoreProvState'));

		$this->registerInternalProperty('country',
			SwatDBClassMap::get('StoreCountry'));

		$this->registerDateProperty('createdate');
	}

	// }}}
	// {{{ protected function displayCA()

	/**
	 * Displays this address in Canada Post format
	 *
	 * Canadian address format rules are taken from {@link Canada Post
	 * http://www.canadapost.ca/personal/tools/pg/manual/PGaddress-e.asp#1383571}
	 */
	protected function displayCA()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'fn';
		$span_tag->setContent($this->getFullName());
		$span_tag->display();
		echo '<br />';

		if (strlen($this->company) > 0) {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo '<br />';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		$span_tag->class = 'street-address';
		$span_tag->setContent($this->line1);
		$span_tag->display();
		echo '<br />';

		if (strlen($this->line2) > 0) {
			$span_tag->class = 'extended-address';
			$span_tag->setContent($this->line2);
			$span_tag->display();
			echo '<br />';
		}

		$span_tag->class = 'locality';
		$span_tag->setContent($this->city);
		$span_tag->display();
		echo ' ';

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif (strlen($this->provstate_other) > 0) {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		echo '&nbsp;&nbsp;';

		$span_tag->class = 'postal-code';
		$span_tag->setContent($this->postal_code);
		$span_tag->display();
		echo '<br />';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if (strlen($this->phone) > 0) {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayGB()

	/**
	 * Displays this address in Royal Mail format
	 *
	 * Formatting rules for UK addresses are taken from
	 * {@link http://www.royalmail.com/portal/rm/content1?catId=400126&mediaId=32700664}.
	 */
	protected function displayGB()
	{
		echo SwatString::minimizeEntities($this->getFullName()), '<br />';

		if (strlen($this->company) > 0)
			echo SwatString::minimizeEntities($this->company), '<br />';

		echo SwatString::minimizeEntities($this->line1), '<br />';

		if (strlen($this->line2) > 0)
			echo SwatString::minimizeEntities($this->line2), '<br />';

		echo SwatString::minimizeEntities($this->city), '<br />';

		if (strlen($this->provstate_other) > 0)
			echo SwatString::minimizeEntities($this->provstate_other), '<br />';

		echo SwatString::minimizeEntities($this->postal_code), '<br />';

		echo SwatString::minimizeEntities($this->country->title), '<br />';

		if (strlen($this->phone) > 0)
			printf('Phone: %s',
				SwatString::minimizeEntities($this->phone));
	}

	// }}}
	// {{{ protected function displayUS()

	/**
	 * Displays this address in US Postal Service format
	 *
	 * American address format rules are taken from
	 * {@link http://pe.usps.gov/text/pub28/28c2_007.html}.
	 */
	protected function displayUS()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'fn';
		$span_tag->setContent($this->getFullName());
		$span_tag->display();
		echo '<br />';

		if (strlen($this->company) > 0) {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo '<br />';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		$span_tag->class = 'street-address';
		$span_tag->setContent($this->line1);
		$span_tag->display();
		echo '<br />';

		if (strlen($this->line2) > 0) {
			$span_tag->class = 'extended-address';
			$span_tag->setContent($this->line2);
			$span_tag->display();
			echo '<br />';
		}

		$span_tag->class = 'locality';
		$span_tag->setContent($this->city);
		$span_tag->display();
		echo ' ';

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif (strlen($this->provstate_other) > 0) {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		echo '&nbsp;&nbsp;';

		$span_tag->class = 'postal-code';
		$span_tag->setContent($this->postal_code);
		$span_tag->display();
		echo '<br />';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if (strlen($this->phone) > 0) {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayCondensedCA()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedCA()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'fn';
		$span_tag->setContent($this->getFullName());
		$span_tag->display();
		echo ', ';

		if (strlen($this->company) > 0) {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo ', ';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		$span_tag->class = 'street-address';
		$span_tag->setContent($this->line1);
		$span_tag->display();

		if (strlen($this->line2) > 0) {
			echo ', ';
			$span_tag->class = 'extended-address';
			$span_tag->setContent($this->line2);
			$span_tag->display();
		}

		echo '<br />';

		$span_tag->class = 'locality';
		$span_tag->setContent($this->city);
		$span_tag->display();
		echo ' ';

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif (strlen($this->provstate_other) > 0) {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			$span_tag->class = 'postal-code';
			$span_tag->setContent($this->postal_code);
			$span_tag->display();
		}

		echo ', ';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if (strlen($this->phone) > 0) {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayCondensedGB()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedGB()
	{
		echo SwatString::minimizeEntities($this->getFullName()), ', ';

		if (strlen($this->company) > 0)
			echo SwatString::minimizeEntities($this->company), ', ';

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

		if (strlen($this->phone) > 0) {
			echo '<br />';
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
	// {{{ protected function displayCondensedUS()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedUS()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'fn';
		$span_tag->setContent($this->getFullName());
		$span_tag->display();
		echo ', ';

		if (strlen($this->company) > 0) {
			$span_tag->class = 'fn org';
			$span_tag->setContent($this->company);
			$span_tag->display();
			echo ', ';
		}

		$address_span_tag = new SwatHtmlTag('span');
		$address_span_tag->class = 'adr';
		$address_span_tag->open();

		$span_tag->class = 'street-address';
		$span_tag->setContent($this->line1);
		$span_tag->display();

		if (strlen($this->line2) > 0) {
			echo ', ';
			$span_tag->class = 'extended-address';
			$span_tag->setContent($this->line2);
			$span_tag->display();
		}

		echo '<br />';

		$span_tag->class = 'locality';
		$span_tag->setContent($this->city);
		$span_tag->display();
		echo ' ';

		if ($this->provstate !== null) {
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'region';
			$abbr_tag->title = $this->provstate->title;
			$abbr_tag->setContent($this->provstate->abbreviation);
			$abbr_tag->display();
		} elseif (strlen($this->provstate_other) > 0) {
			$span_tag->class = 'region';
			$span_tag->setContent($this->provstate_other);
			$span_tag->display();
		}

		if ($this->postal_code !== null) {
			echo '&nbsp;&nbsp;';
			$span_tag->class = 'postal-code';
			$span_tag->setContent($this->postal_code);
			$span_tag->display();
		}

		echo ', ';

		$span_tag->class = 'country-name';
		$span_tag->setContent($this->country->title);
		$span_tag->display();

		$address_span_tag->close();

		if (strlen($this->phone) > 0) {
			echo '<br />', Store::_('Phone: ');
			$span_tag->class = 'tel';
			$span_tag->setContent($this->phone);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayCondensedAsTextCA()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextCA()
	{
		echo $this->getFullName(), ', ';

		if (strlen($this->company) > 0)
			echo $this->company, ', ';

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

		if (strlen($this->phone) > 0) {
			echo "\n";
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
	// {{{ protected function displayCondensedAsTextGB()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextGB()
	{
		echo $this->getFullName(), ', ';

		if (strlen($this->company) > 0)
			echo $this->company, ', ';

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

		if (strlen($this->phone) > 0) {
			echo "\n";
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
	// {{{ protected function displayCondensedAsTextUS()

	/**
	 * Displays this address in a two-line condensed form
	 */
	protected function displayCondensedAsTextUS()
	{
		echo $this->getFullName(), ', ';

		if (strlen($this->company) > 0)
			echo $this->company, ', ';

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

		if (strlen($this->phone) > 0) {
			echo "\n";
			printf(Store::_('Phone: %s'),
				SwatString::minimizeEntities($this->phone));
		}
	}

	// }}}
}

?>
