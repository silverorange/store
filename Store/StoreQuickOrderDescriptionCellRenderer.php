<?php

require_once 'Swat/SwatWidgetCellRenderer.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreQuickOrderDescriptionCellRenderer extends SwatWidgetCellRenderer
{
	// {{{ public function getThAttributes()

	public function getThAttributes()
	{
		return array('class' => 'store-quick-order-description-header');
	}

	// }}}
	// {{{ public function getTdAttributes()

	public function getTdAttributes()
	{
		return array('class' => 'store-quick-order-description-cell');
	}

	// }}}
	// {{{ public function render()

	public function render()
	{
		echo '<div id="'.$this->id.'_'.$this->replicator_id.'">';
		parent::render();
		echo '</div>';
	}

	// }}}
}

?>
