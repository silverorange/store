<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'Admin/AdminUI.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Priority page for Features
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeaturePriority extends AdminDBOrder
{
	// {{{ private properties

	private $slot;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->slot = SiteApplication::initVar('slot');
		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('slot', $this->slot);
	}

	// }}}

	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'Feature',
			'integer:priority', $index, 'integer:id', array($id));

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()
	protected function buildInternal()
	{
		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Admin::_('Priority');
		parent::buildInternal();
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$where_clause = sprintf('display_slot = %s',
			$this->app->db->quote($this->slot, 'integer'));

		$order_widget = $this->ui->getWidget('order');
		$order_widget->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'Feature', 'title', 'id', 'priority, start_date',
			$where_clause));

		$sql = 'select sum(priority) from Feature where '.
			$where_clause;

		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
}

?>
