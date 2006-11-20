<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTreeFlydownNode.php';
require_once 'Swat/SwatFlydownDivider.php';
require_once 'Store/StoreCatalogSelector.php';

require_once 
	'Store/admin/components/Product/include/StoreProductSearchWhereClause.php';

require_once 
	'Store/admin/components/Product/include/Store/ProductTableView.php';

require_once 
	'Store/admin/components/Product/include/StoreProductTitleCellRenderer.php';

require_once
	'Store/admin/components/Product/include/StoreItemStatusCellRenderer.php';

/**
 * Index page for Products
 *
 * @package   veseys2
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductIndex extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Product/include/index.xml';
	protected $search_xml = 'Store/admin/components/Product/include/search.xml';

	// }}}
	// {{{ private properties

	private $quick_sku_search = false;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->search_xml);
		$this->ui->loadFromXML($this->index_xml);

		$catalog_selector = $this->ui->getWidget('catalog_selector');
		$catalog_selector->db = $this->app->db;

		$products_frame = $this->ui->getWidget('results_frame');
		$products_frame->addStyleSheet(
			'packages/store/admin/styles/disabled-rows.css');

		$quick_search_form = $this->layout->menu->getForm();
		$quick_search_form->process();

		if ($quick_search_form->isProcessed())
			$this->clearState();
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$quick_search_form = $this->layout->menu->getForm();

		if ($quick_search_form->isProcessed()) {
			$sku = $this->layout->menu->getItemEntry();
			$search_item = $this->ui->getWidget('search_item');
			$search_item->value = $sku->value;
			$search_item_op = $this->ui->getWidget('search_item_op');
			$search_item_op->value = AdminSearchClause::OP_CONTAINS;
			$this->quick_sku_search = true;

			$this->saveState();

			$this->quickSKUSearch();
		}

		$index = $this->ui->getWidget('results_frame');
		if ($this->hasState() && $index->visible === false) {
			$this->loadState();
			$index->visible = true;
		}

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db,
			sprintf('select count(id) from Product where %s',
				$this->getWhereClause()));

		$pager->process();
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Product/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}
	// {{{ protected function quickSKUSearch()

	protected function quickSKUSearch()
	{
		$search_item = $this->ui->getWidget('search_item');

		$sql = sprintf('select id from Product
			where id in (select product from Item where sku = %s)',
			$this->app->db->quote($search_item->value, 'text'));

		$products = SwatDB::query($this->app->db, $sql);

		if (count($products) == 1) {
			$product = $products->getFirst();
			$this->app->relocate('Product/Details?id='.$product->id);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$rs = SwatDB::executeStoredProc($this->app->db, 'getCategoryTree', 
			'null');

		$tree = new SwatTreeFlydownNode('Root', '');
		$tree->addChild(new SwatTreeFlydownNode(-1,
			Store::_('<uncategorized>')));

		$tree->addChild(new SwatTreeFlydownNode(new SwatFlydownDivider()));
		SwatDB::buildTreeOptionArray($rs, 'title', 'id', 'levelnum', $tree);

		$tree_flydown = $this->ui->getWidget('search_category');
		$tree_flydown->setTree($tree);
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$where_clause = new StoreProductSearchWhereClause($this->ui,
			$this->app->db);

		return $where_clause->getWhereClause();
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = sprintf('select count(id) from Product where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select Product.id,
					Product.title,
					statuses.count_total as item_count,
					statuses.count_available,
					statuses.count_outofstock,
					statuses.count_disabled
				from Product
					inner join ProductItemCountByStatusView as statuses on
						statuses.product = Product.id
				where %s
				order by %s';

		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view,
				'Product.title, Product.id'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		if ($store->getRowCount() != 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');


		return $store;
	}

	// }}}
}

?>
