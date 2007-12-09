<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A sale with a percentage discount
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItem
 */
class StoreSaleDiscount extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier for this sale
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * A short, textual identifier for this sale
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * A title for describing this sale
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Start data
	 *
	 * @var date
	 */
	public $start_date;

	/**
	 * End date
	 *
	 * @var date
	 */
	public $end_date;

	// }}}
	// {{{ public function isActive()

	/**
	 * Checks if this sale is currently active
	 *
	 * @return boolean true if this sale is active and false if it is not.
	 */
	public function isActive()
	{
		$now = new SwatDate();
		$now->toUTC();

		return
			(($this->start_date === null ||
				SwatDate::compare($now, $this->start_date) >= 0) &&
			($this->end_date === null ||
				SwatDate::compare($now, $this->end_date) <= 0));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'SaleDiscount';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('start_date');
		$this->registerDateProperty('end_date');
	}

	// }}}
}

?>
