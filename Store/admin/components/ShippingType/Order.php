<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Order page for Shipping Types
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeOrder extends AdminDBOrder
{
	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'ShippingType',
			'integer:displayorder', $index, 'integer:id', array($id));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()
	protected function buildInternal()
	{
		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Store::_('Order Shipping Types');
		parent::buildInternal();
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$order_widget = $this->ui->getWidget('order');
		$order_widget->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'ShippingType', 'title', 'id', 'displayorder'));

		$sql = 'select sum(displayorder) from ShippingType';

		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
}

?>
