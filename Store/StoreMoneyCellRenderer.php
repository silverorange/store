<?php

require_once 'Swat/SwatMoneyCellRenderer.php';

/**
 * Money cell renderer that displays n/a when no value is available
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreMoneyCellRenderer extends SwatMoneyCellRenderer
{
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();
		$this->null_display_value = Store::_('n/a');
	}

	// }}}
	// {{{ public function display()

	public function display(SwatDisplayContext $context)
	{
		if (!$this->visible) {
			return;
		}

		parent::display($context);

		$context->addStyleSheet('packages/swat/styles/swat.css');
	}

	// }}}
}

?>
