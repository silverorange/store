<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Order page for ItemGroups component
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroupOrder extends AdminDBOrder
{
	// {{{ private properties

	private $product_id;
	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->product_id = SiteApplication::initVar('product');
		$this->category_id = SiteApplication::initVar('category');
	}

	// }}}

	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'ItemGroup',
			'integer:displayorder', $index, 'integer:id', array($id));
	}

	// }}}
	// {{{ protected function getUpdatedMessage()

	protected function getUpdatedMessage()
	{
		return new SwatMessage(Store::_('Item group order updated.'));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Store::_('Order Item Groups');

		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('product', $this->product_id);
		$form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$where_clause = sprintf('product = %s',
			$this->app->db->quote($this->product_id, 'integer'));

		$order_widget = $this->ui->getWidget('order');
		$order_widget->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'ItemGroup', 'title', 'id', 'displayorder, title', $where_clause));

		$sql = 'select sum(displayorder) from ItemGroup where '.$where_clause;
		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->popEntries(2);

		if ($this->category_id === null) {
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Search'), 'Product'));

		} else {
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry)
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
		}

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $this->product_id);

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $this->product_id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product_id, $this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Change Group Order')));

		$this->title = $product_title;
	}

	// }}}
}

?>
