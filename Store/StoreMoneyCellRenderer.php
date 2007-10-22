<?php

require_once 'Swat/SwatMoneyCellRenderer.php';

/** 
 * Money cell renderer that displays n/a when no value is available
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreMoneyCellRenderer extends SwatMoneyCellRenderer
{
	// {{{ public function __construct

	public function __construct()
	{
		parent::__construct();

		$this->addStyleSheet('packages/swat/styles/swat.css',
			Swat::PACKAGE_ID);
	}

	// }}}
	// {{{ public function render()

	public function render()
	{
		if ($this->value === null) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-none';
			$span_tag->setContent('n/a');
			$span_tag->display();
		} else {
			parent::render();
		}
	}

	// }}}
}

?>
