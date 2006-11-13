<?php

require_once 'Store/dataobjects/StoreAddress.php';
require_once 'Swat/SwatConfirmationButton.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatControl.php';

/**
 * A viewer for an address object
 *
 * This view contains a link to edit the address and a button to remove the
 * address. This view is a Swat widget and can exist in the Swat widget tree.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddressView extends SwatControl
{
	// {{{ public properties

	public $address;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $edit_address_link = 'account/address%s';

	// }}}
	// {{{ private properties

	/**
	 * @var SwatButton
	 */
	private $remove_button;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->remove_button =
			new SwatConfirmationButton($this->id);

		$this->remove_button->parent = $this;
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$this->remove_button->process();
	}

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
		$div->class = $this->getCssClassString();

		$controls = new SwatHtmlTag('div');
		$controls->class = 'store-address-view-controls';

		$edit_link = new SwatToolLink();
		$edit_link->link = sprintf($this->edit_address_link,
			$this->address->id);

		$edit_link->title = Store::_('Edit Address');
		$edit_link->setFromStock('edit');

		$this->remove_button->title = Store::_('Remove');
		$this->remove_button->classes[] = 'store-remove';
		$this->remove_button->confirmation_message = sprintf(Store::_(
			"Are you sure you want to remove the following address?\n\n%s"),
			$address_text);

		$div->open();
		$this->address->displayCondensed();
		$controls->open();
		$edit_link->display();
		$this->remove_button->display();
		$controls->close();
		$div->close();
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this address view
	 *
	 * @return array the array of CSS classes that are applied to this address 
	 *                view.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('store-address-view');
		$classes = array_merge($classes, $this->classes);
		return $classes;
	}

	// }}}
}

?>
