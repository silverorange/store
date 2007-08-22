<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTreeFlydownNode.php';
require_once 'Swat/SwatFlydownDivider.php';
require_once 'Store/StoreCatalogSelector.php';
require_once 'Store/admin/components/Product/include/StoreProductSearch.php';

/**
 * Search page for Related Products
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductRelatedProduct extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Product/related-product.xml';
	protected $search_xml = 'Store/admin/components/Product/search.xml';

	// }}}
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

		$this->ui->loadFromXML($this->search_xml);
		$this->ui->loadFromXML($this->ui_xml);

		$catalog_selector = $this->ui->getWidget('catalog_selector');
		$catalog_selector->db = $this->app->db;
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
				$sql = 'insert into ProductRelatedProductBinding
						(source_product, related_product)
					select %1$s, Product.id from Product where
						Product.id not in (
							select related_product
							from ProductRelatedProductBinding
							where source_product = %1$s)
						and Product.id in (%2$s)';

				$sql = sprintf($sql,
					$this->app->db->quote($this->product_id, 'integer'),
					SwatDB::implodeSelection(
						$this->app->db, $view->getSelection(), 'integer'));

				$num = SwatDB::exec($this->app->db, $sql);

				$message = new SwatMessage(sprintf(Store::ngettext(
					'One product has been related to this product.',
					'%s products have been related to this product.', $num),
					SwatString::numberFormat($num)),
					SwatMessage::NOTIFICATION);

				$this->app->messages->add($message);
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
		$this->buildNavBar();

		$category_flydown = $this->ui->getWidget('search_category');

		$tree = $category_flydown->getTree();
		$tree->addChild(new SwatTreeFlydownNode(-1, '<uncategorized>'));
		$tree->addChild(new SwatTreeFlydownNode(new SwatFlydownDivider()));

		$rs = SwatDB::executeStoredProc($this->app->db, 'getCategoryTree',
			'null');

		$category_tree = SwatDB::getDataTree($rs, 'title', 'id', 'levelnum');
		$tree->addTree($category_tree);

		$search_frame = $this->ui->getWidget('search_frame');
		$search_frame->title = Store::_('Search for Related Products to Add');

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
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		if ($this->category_id !== null) {
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry)
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
		}

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $this->product_id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product_id, $this->category_id);

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $this->product_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Add Related Products')));

		$this->title = $product_title;
	}

	// }}}
}

?>
