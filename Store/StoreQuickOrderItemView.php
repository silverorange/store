<?php

require_once 'Swat/SwatFlydown.php';

/**
 * 
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreQuickOrderItemView extends SwatFlydown
{
	// {{{ public properties

	public $product_title;

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->product_title !== null) {
			if (count($this->getOptions()) > 1) {
				$label_tag = new SwatHtmlTag('label');
				$label_tag->for = $this->id;
				$label_tag->setContent($this->product_title.':');
				$label_tag->display();
				echo ' ';
			} else {
				echo $this->product_title.': ';
			}
			echo '<br />';
		}
		parent::display();
	}

	// }}}
}

?>
