<?php

require_once 'Swat/SwatControl.php';
require_once 'VanBourgondien/VanBourgondien.php';

/**
 * @package   VanBourgondien
 * @copyright 2007 silverorange
 */
class VanBorgondienCheckoutProgress extends SwatControl
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
			'packages/van-bourgondien/styles/checkout-progress.css',
			VanBourgondien::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		$ol_tag = new SwatHtmlTag('ol');
		$ol_tag->class = 'checkout-progress';
		$ol_tag->id = $this->id;
		$ol_tag->open();

		foreach ($this->getSteps() as $id => $step) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->class = 'checkout-progress-step'.$id;
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
			'1' => 'Your Information',
			'2' => 'Review Order',
			'3' => 'Order Completed',
		);
	}

	// }}}
}

?>
