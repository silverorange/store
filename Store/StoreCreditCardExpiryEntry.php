<?php

require_once 'Swat/SwatDateEntry.php';
require_once 'Swat/SwatMessage.php';

/**
 * A widget for basic validation of a credit card
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCreditCardExpiryEntry extends SwatDateEntry
{
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->show_month_number = true;
		$this->display_parts = self::MONTH|self::YEAR;

		// do not allow dates in the past
		$this->setValidRange(0, 20);
		$this->valid_range_start->setMonth(Date_Calc::getMonth());
	}

	// }}}
	// {{{ public function isValid()

	public function isValid()
	{
		if (Date::compare($this->value, $this->valid_range_start, true) < 0)
			return false;
		else
			return true;
	}

	// }}}
}

?>
