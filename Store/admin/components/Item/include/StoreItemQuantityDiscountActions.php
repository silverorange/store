<?php

require_once 'Swat/SwatActions.php';

/**
 * Index actions for the quantity discounts page
 *
 * The quantity discounts page has both an apply and a done button. The apply
 * button behaves as a normal index actions and the done button performs
 * actions and then goes back to the product details page.
 *
 * @package   Store
 * @copyright 2006-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemQuantityDiscountActions extends SwatActions
{
	// {{{ public function getDoneButton()

	public function getDoneButton()
	{
		return $this->getCompositeWidget('done_button');
	}

	// }}}
	// {{{ protected function displayButton()

	protected function displayButton()
	{
		parent::displayButton();

		echo '&nbsp;&nbsp;';

		$button = $this->getCompositeWidget('done_button');
		$button->setFromStock('submit');
		$button->title = Store::_('Done');
		$button->display();
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		parent::createCompositeWidgets();

		$button = new SwatButton($this->id.'_done_button');
		$this->addCompositeWidget($button, 'done_button');
	}

	// }}}
}

?>
