<?php

require_once 'Store/dataobjects/StoreAddress.php';
require_once 'Swat/SwatConfirmationButton.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatControl.php';

/**
 * A viewer for an address object.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

class StoreAddressView extends SwatControl
{
	// {{{ public properties

	public $address;

	// }}}
	// {{{ private properties

	private $remove_button;
	private $edit_address_link = 'account/address%s';

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->remove_button =
			new SwatConfirmationButton($this->id);
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$this->remove_button->process();
	}

	// }}}
	// {{{ public function hasBeenClicked()

	public function hasBeenClicked()
	{
		return $this->remove_button->hasBeenClicked();
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();
		$set->addEntrySet($this->remove_button->getHtmlHeadEntrySet());
		return $set;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		ob_start();
		$this->address->displayCondensedAsText();
		$address_text = ob_get_clean();

		$div = new SwatHtmlTag('div');
		$div->class = 'store-address';

		$controls = new SwatHtmlTag('div');
		$controls->class = 'store-address-controls';

		$edit_link = new SwatToolLink();
		$edit_link->link = sprintf($this->edit_address_link, $this->address->id);
		$edit_link->title = 'Edit Address';
		$edit_link->setFromStock('edit');

		$this->remove_button->title = 'Remove Address';
		$this->remove_button->class = 'store-remove';
		$this->remove_button->confirmation_message = sprintf(
			"Are you sure you want to remove the following address?\n\n%s",
			$address_text);

		$div->open();
			$controls->open();
				$edit_link->display();
				$this->remove_button->display();
			$controls->close();
			$this->address->displayCondensed();
		$div->close();
	}

	// }}}
}

?>
