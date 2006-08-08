<?php

require_once 'Swat/SwatWidgetCellRenderer.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreQuickOrderDescriptionCellRenderer extends SwatWidgetCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id.'_'.$this->replicator_id;
		$div_tag->open();
		parent::render();
		$div_tag->close();
	}

	// }}}
	// {{{ public function getBaseCSSClassNames()

	public function getBaseCSSClassNames()
	{
		return array('store-quick-order-description-cell');
	}

	// }}}
}

?>
