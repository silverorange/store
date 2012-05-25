<?php

require_once 'Swat/SwatControl.php';
require_once 'Store/Store.php';

/**
 * @package   Store
 * @copyright 2007-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutProgress extends SwatControl
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $current_step = 0;

	// }}}
	// {{{ public function display()

	public function display(SwatDisplayContext $contex)
	{
		if (!$this->visible) {
			return;
		}

		parent::display($context);

		$ol_tag = new SwatHtmlTag('ol');
		$ol_tag->id = $this->id;
		if ($this->current_step > 0) {
			$ol_tag->class =
				' store-checkout-progress-step'.$this->current_step;
		}

		$context->out('<div class="store-checkout-progress">');
		$ol_tag->open($context);

		foreach ($this->getSteps() as $id => $step) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->class = 'store-checkout-progress-step'.$id;
			$li_tag->open($context);

			$context->out(
				sprintf(
					'<span class="title"><span class="number">'.
					'%s.</span> %s</span>',
					$id,
					$step
				)
			);

			$li_tag->close($context);
		}

		$ol_tag->close($context);
		$context->out('<div class="store-checkout-progress-clear"></div>');
		$context->out('</div>');

		$context->addStyleSheet(
			'packages/store/styles/store-checkout-progress.css'
		);
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
