<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'Admin/AdminUI.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Order page for Attributes
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeOrder extends AdminDBOrder
{
	// {{{ private properties

	private $parent;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->parent = SiteApplication::initVar('parent');
		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('parent', $this->parent);
	}

	// }}}

	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'Attribute',
			'integer:displayorder', $index, 'integer:id', array($id));

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()
	protected function buildInternal()
	{
		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Store::_('Order Attributes');
		parent::buildInternal();
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$where_clause = sprintf('attribute_type = %s',
			$this->app->db->quote($this->parent, 'integer'));

		$order_widget = $this->ui->getWidget('order');
		$order_widget->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'Attribute', 'title', 'id', 'displayorder, title',
			$where_clause));

		$sql = 'select sum(displayorder) from Attribute where '.
			$where_clause;

		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
}

?>
