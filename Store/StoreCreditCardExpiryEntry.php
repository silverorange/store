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

		$this->setValidRange(0, 20);
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		parent::process();

	}

	// }}}
	// {{{ public function showEditMessage()

	/*
	 * Checks the validity of the current set date
	 *
	 * This method is useful when editing an expiry date to notify the
	 * customer that they must update it.
	 */
	public function showEditMessage()
	{
		$content = sprintf('The expiry date that was entered (%s)
			is in the past. Please enter an updated date.',
			$this->value->format(SwatDate::DF_MY));

		$message = new SwatMessage($content, SwatMessage::ERROR);
		$this->addMessage($message);

		$this->value = null;
	}

	// }}}
}

?>
