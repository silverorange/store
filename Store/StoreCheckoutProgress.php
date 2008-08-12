<?php

require_once 'Swat/SwatControl.php';
require_once 'Store/Store.php';

/**
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreCheckoutProgress extends SwatControl
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $current_step = 1;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->addStyleSheet(
			'packages/store/styles/store-checkout-progress.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		$ol_tag = new SwatHtmlTag('ol');
		$ol_tag->class = 'store-checkout-progress';
		$ol_tag->id = $this->id;
		$ol_tag->open();

		foreach ($this->getSteps() as $id => $step) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->class = 'store-checkout-progress-step'.$id;
			$li_tag->setContent($step);
			$li_tag->display();
		}

		$ol_tag->close();
	}

	// }}}
	// {{{ private static function getSteps()

	private static function getSteps()
	{
		return array(
			'1' => Store::_('Your Information'),
			'2' => Store::_('Review Order'),
			'3' => Store::_('Order Completed'),
		);
	}

	// }}}
}

?>
