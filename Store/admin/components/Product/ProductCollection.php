<?php

/**
 * Search page for Product Collections
 *
 * @package   Store
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductProductCollection extends AdminSearch
{
	// {{{ private properties

	private $category_id;
	private $product_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->product_id = SiteApplication::initVar('product');
		$this->category_id = SiteApplication::initVar('category');

		$this->ui->loadFromXML($this->getSearchXml());
		$this->ui->loadFromXML($this->getUiXml());

		$this->initCatalogSelector();

		$status_array = array();
		foreach (StoreItemStatusList::statuses() as $status) {
			$status_array[$status->id] = $status->title;
		}
		$status_flydown = $this->ui->getWidget('search_item_status');
		$status_flydown->addOptionsByArray($status_array);

		$sale_discount_flydown = $this->ui->getWidget('search_sale_discount');
		$sale_discount_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'SaleDiscount', 'title', 'id', 'title'));

		$search_flydown = $this->ui->getWidget(
			'search_item_minimum_quantity_group');

		$options = SwatDB::getOptionArray($this->app->db,
			'ItemMinimumQuantityGroup', 'title', 'id', 'title');

		$search_flydown->addOptionsByArray($options);

		$this->ui->getWidget('search_item_minimum_quantity_group_field'
			)->visible = (count($options) > 0);
	}

	// }}}
	// {{{ private function initCatalogSelector()

	/**
	 * Builds the catalog selector. Selector does not get shown unless there is
	 * more than one catalog, as its not useful when there is only one.
	 */
	private function initCatalogSelector()
	{
		$this->ui->getWidget('catalog_selector')->db = $this->app->db;

		$sql = 'select count(id) from Catalog';
		$catalog_count = SwatDB::queryOne($this->app->db, $sql);
		if ($catalog_count == 1)
			$this->ui->getWidget('catalog_field')->visible = false;
	}

	// }}}
	// {{{ protected function getSearchXml()

	protected function getSearchXml()
	{
		return __DIR__.'/search.xml';
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/product-collection.xml';
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('index_form');
		$view = $this->ui->getWidget('index_view');

		if ($form->isProcessed()) {
			if (count($view->getSelection()) != 0) {

				// relate products
				$sql = 'insert into ProductCollectionBinding
						(member_product, source_product)
					select Product.id, %1$s from Product where
						Product.id not in (
							select member_product
							from ProductCollectionBinding
							where source_product = %1$s)
						and Product.id in (%2$s)';

				$sql = sprintf($sql,
					$this->app->db->quote($this->product_id, 'integer'),
					SwatDB::implodeSelection(
						$this->app->db, $view->getSelection(), 'integer'));

				$num = SwatDB::exec($this->app->db, $sql);

				$message = new SwatMessage(sprintf(Store::ngettext(
					'One product has been added to this collection.',
					'%s product have been added to this collection.', $num),
					SwatString::numberFormat($num)),
					'notice');

				$this->app->messages->add($message);

				if (isset($this->app->memcache))
					$this->app->memcache->flushNs('product');
			}

			if ($this->category_id === null)
				$this->app->relocate('Product/Details?id='.$this->product_id);
			else
				$this->app->relocate('Product/Details?id='.$this->product_id.
					'&category='.$this->category_id);
		}

		$pager = $this->ui->getWidget('pager');
		$pager->process();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$category_flydown = $this->ui->getWidget('search_category');

		$tree = $category_flydown->getTree();
		$tree->addChild(new SwatTreeFlydownNode(-1, '<uncategorized>'));
		$tree->addChild(new SwatTreeFlydownNode(new SwatFlydownDivider()));

		$rs = SwatDB::executeStoredProc($this->app->db, 'getCategoryTree',
			'null');

		$category_tree = SwatDB::getDataTree($rs, 'title', 'id', 'levelnum');
		$tree->addTree($category_tree);

		$search_frame = $this->ui->getWidget('search_frame');
		$search_frame->title =
			Store::_('Search for Products to Add to the Collection');

		$search_form = $this->ui->getWidget('search_form');
		$search_form->action = $this->getRelativeURL();

		$form = $this->ui->getWidget('index_form');
		$form->action = $this->getRelativeURL();
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$search = $this->getProductSearch();

		$sql = sprintf('select count(id) from Product %s where %s',
			$search->getJoinClause(),
			$search->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select id, title from Product %s where %s order by %s';
		$sql = sprintf($sql,
			$search->getJoinClause(),
			$search->getWhereClause(),
			$this->getOrderByClause($view, $search->getOrderByClause()));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$rs = SwatDB::query($this->app->db, $sql);

		return $rs;
	}

	// }}}
	// {{{ protected function getProductSearch()

	/**
	 * Gets the product search object
	 *
	 * @return StoreProductSearch the product search object.
	 */
	protected function getProductSearch()
	{
		return new StoreProductSearch($this->ui, $this->app->db);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->category_id !== null) {
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry) {
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
			}
		}

		if ($this->category_id === null) {
			$link = sprintf('Product/Details?id=%s', $this->product_id);
		} else {
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product_id, $this->category_id);
		}

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $this->product_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Add Products to the Collection')));

		$this->title = $product_title;
	}

	// }}}
}

?>
