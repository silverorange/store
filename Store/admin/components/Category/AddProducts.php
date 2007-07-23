<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTreeFlydownNode.php';
require_once 'Swat/SwatFlydownDivider.php';
require_once 'Store/StoreCatalogSelector.php';
require_once 'Store/admin/components/Product/include/StoreProductSearch.php';

/**
 * Search page for add products to category tool
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryAddProducts extends AdminSearch
{
	// {{{ private properties

	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML('Store/admin/components/Product/search.xml');
		$this->ui->loadFromXML(
			'Store/admin/components/Category/addproducts.xml');

		$catalog_selector = $this->ui->getWidget('catalog_selector');
		$catalog_selector->db = $this->app->db;

		$this->category_id = SiteApplication::initVar('category');
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('index_form');

		if ($form->isProcessed()) {
			$view = $this->ui->getWidget('index_view');

			if (count($view->getSelection()) > 0) {

				$product_list = array();
				foreach ($view->getSelection() as $item)
					$product_list[] = $this->app->db->quote($item, 'integer');

				$sql = sprintf('insert into CategoryProductBinding
					(category, product)
					select %s, Product.id from Product
					where Product.id
						not in (select product from CategoryProductBinding
						where category = %s)
					and Product.id in (%s)',
					$this->app->db->quote($this->category_id, 'integer'),
					$this->app->db->quote($this->category_id, 'integer'),
					implode(',', $product_list));

				$num = SwatDB::exec($this->app->db, $sql);

				$message = new SwatMessage(sprintf(Store::ngettext(
					'One product has been added to this category.',
					'%d products have been added to this category.', $num),
					SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

				$this->app->messages->add($message);
			}

			$this->app->relocate('Category/Index?id='.$this->category_id);
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

		$tree->addChild(new SwatTreeFlydownNode(-1,
			Store::_('<uncategorized>')));

		$tree->addChild(new SwatTreeFlydownNode(new SwatFlydownDivider('')));

		$rs = SwatDB::executeStoredProc($this->app->db, 'getCategoryTree',
			array('null'));

		$category_tree = SwatDB::getDataTree($rs, 'title', 'id', 'levelnum');
		$tree->addTree($category_tree);

		$search_frame = $this->ui->getWidget('search_frame');
		$search_frame->title = Store::_('Search for Products to Add');

		$search_form = $this->ui->getWidget('search_form');
		$search_form->action = $this->getRelativeURL();
		$search_form->addHiddenField('category', $this->category_id);

		$index_form = $this->ui->getWidget('index_form');
		$index_form->action = $this->source;
		$index_form->addHiddenField('category', $this->category_id);
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
			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));
			
			foreach ($navbar_rs as $row) {
				$this->title = $row->title;
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
			}
		}

		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Add Products')));
	}

	// }}}
}

?>
