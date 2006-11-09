<?php

require_once 'Swat/SwatActions.php';

/**
 * Index actions for the quantity discounts page
 *
 * The quantity discounts page has bot an apply and a done button. The apply
 * button behaves as a normal index actions and the done button performs
 * actions and then goes back to the product details page.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemQuantityDiscountActions extends SwatActions
{
	private $done_button;

	public function getDoneButton()
	{
		return $this->done_button;
	}

	public function process()
	{
		parent::process();
		$this->done_button->process();
	}

	protected function displayButton()
	{
		parent::displayButton();
		echo str_repeat('&nbsp;', 2);
		$this->done_button->display();
	}

	protected function createEmbeddedWidgets()
	{
		parent::createEmbeddedWidgets();
		$this->done_button = new SwatButton($this->id.'_done_button');
		$this->done_button->parent = $this;
		$this->done_button->setFromStock('submit');
		$this->done_button->title = Store::_('Done');
	}
}

?>
