<?php

require_once 'Store/dataobjects/StoreVoucher.php';
require_once 'Swat/SwatConfirmationButton.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatControl.php';

/**
 * A viewer for an voucher object
 *
 * This view contains a button to remove the voucher.
 * This view is a Swat widget and can exist in the Swat widget tree.
 *
 * @package   Store
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreVoucherView extends SwatControl
{
	// {{{ public properties

	/**
	 * @var StoreVoucher
	 */
	public $voucher;

	/**
	 * @var boolean
	 */
	public $show_remove_button = true;

	// }}}
	// {{{ public function hasBeenClicked()

	/**
	 * Whether or not the 'remove' button of this view whas been clicked
	 *
	 * @return boolean true if the remove button has been clicked and false if
	 *                  it has not.
	 */
	public function hasBeenClicked()
	{
		$remove_button = $this->getCompositeWidget('remove_button');
		return $remove_button->hasBeenClicked();
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		$remove_button = $this->getCompositeWidget('remove_button');
		$remove_button->title = Store::_('Remove');
		$remove_button->classes[] = 'store-remove';
		$remove_button->classes[] = 'compact-button';
		$remove_button->confirmation_message = sprintf(
			Store::_('Are you sure you want to remove %s?'),
			$this->voucher->getTitle()
		);

		$div = new SwatHtmlTag('div');
		$div->open();

		echo SwatString::minimizeEntities(
			$this->voucher->getTitleWithAmount()
		);

		if ($this->show_remove_button) {
			$remove_button->display();
		}

		$div->close();
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	/**
	 * Creates and adds composite widgets of this widget
	 */
	protected function createCompositeWidgets()
	{
		$remove_button = new SwatConfirmationButton($this->id);
		$this->addCompositeWidget($remove_button, 'remove_button');
	}

	// }}}
}

?>
