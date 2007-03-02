<?php

require_once 'Swat/SwatDateEntry.php';

/**
 * A widget for entry of payment card dates
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCardDateEntry extends SwatDateEntry
{
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->show_month_number = true;
		$this->display_parts = self::MONTH | self::YEAR;

		// do not allow dates in the past by default
		$this->setValidRange(0, 20);

		// set start date fields to first day of the current month
		$today = new Date();
		$this->valid_range_start->setMonth($today->getMonth());
		$this->valid_range_start->setDay(1);
		$this->valid_range_start->setHour(0);
		$this->valid_range_start->setMinute(0);
		$this->valid_range_start->setSecond(0);
	}

	// }}}
}

?>
