<?php

require_once 'Admin/AdminTableStore.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/StoreCatalogSwitcher.php';
require_once 'Store/dataobjects/StoreCategory.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/StoreClassMap.php';

//TODO - move some of these into better locations
require_once 'Store/admin/components/Category/include/'.
	'StoreCategoryTableView.php';

require_once 'Store/admin/components/Category/include/'.
	'StoreCategoryTitleCellRenderer.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreItemStatusCellRenderer.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreProductTableView.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreProductTitleCellRenderer.php';

/**
 * Index page for Categories
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryIndex extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Category/index.xml';
	protected $id = null;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->ui->getRoot()->addStyleSheet(
			'packages/store/admin/styles/store-image-preview.css');

		$this->ui->getWidget('catalog_switcher')->db = $this->app->db;
		$this->id = SiteApplication::initVar('id', null,
			SiteApplication::VAR_GET);
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($view->id) {
		case 'categories_index_view':
			$this->processCategoryActions($view, $actions);
			return;

		case 'products_index_view':
			$this->processProductActions($view, $actions);
			return;

		case 'featured_products_index_view':
			$this->processFeaturedProductActions($view, $actions);
			return;
		}
	}

	// }}}
	// {{{ private function processCategoryActions()

	private function processCategoryActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'categories_delete':
			$this->app->replacePage('Category/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;

		case 'categories_remove_products':
			$this->app->replacePage('Category/RemoveProducts');
			$this->app->getPage()->setItems($view->checked_items);
			$this->app->getPage()->setCategory($this->id);
			break;

		case 'categories_change_status':
			$this->app->replacePage('Category/ChangeItemStatus');
			$this->app->getPage()->setItems($view->checked_items);
			$this->app->getPage()->setCategory($this->id);
			$this->app->getPage()->setStatus(
				$this->ui->getWidget('categories_status')->value);

			break;

		case 'categories_enable_items':
			$this->app->replacePage('Category/SetItemEnabled');
			$this->app->getPage()->setItems($view->checked_items);
			$this->app->getPage()->setCategory($this->id);
			$this->app->getPage()->setEnabled(true);
			$this->app->getPage()->setRegion(
				$this->ui->getWidget('categories_enable_region')->value);

			break;

		case 'categories_disable_items':
			$this->app->replacePage('Category/SetItemEnabled');
			$this->app->getPage()->setItems($view->checked_items);
			$this->app->getPage()->setCategory($this->id);
			$this->app->getPage()->setEnabled(false);
			$this->app->getPage()->setRegion(
				$this->ui->getWidget('categories_disable_region')->value);

			break;
		}
	}

	// }}}
	// {{{ private function processProductActions()

	private function processProductActions($view, $actions)
	{
		$num = count($view->checked_items);
		$message = null;

		$item_list = array();
		foreach ($view->checked_items as $item)
			$item_list[] = $this->app->db->quote($item, 'integer');

		switch ($actions->selected->id) {
		case 'products_delete':
			$this->app->replacePage('Product/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			$this->app->getPage()->setCategory($this->id);
			break;

		case 'products_remove':
			SwatDB::query($this->app->db, sprintf('
				delete from CategoryProductBinding
				where category = %s and product in (%s)',
				$this->app->db->quote($this->id, 'integer'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One product has been removed from this category.',
				'%s products have been removed from this category.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'products_change_status' :
			$new_status = $this->ui->getWidget('products_status')->value;

			$num = SwatDB::queryOne($this->app->db, sprintf(
				'select count(id) from Item where product in (%s)',
				implode(',', $view->checked_items)));

			SwatDB::updateColumn($this->app->db, 'Item', 'integer:status',
				$new_status, 'product', $item_list);

			$message = new SwatMessage(sprintf(Store::ngettext(
				'The status of one item has been changed.',
				'The status of %s items has been changed.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'products_set_minor' :
		case 'products_unset_minor' :
			$sql = 'update CategoryProductBinding set minor = %s
				where product in (%s) and category = %s';

			if ($actions->selected->id === 'products_set_minor')
				$minor = true;
			else
				$minor = false;

			$sql = sprintf($sql,
				$this->app->db->quote($minor, 'boolean'),
				implode(',', $view->checked_items),
				$this->app->db->quote($this->id, 'integer'));

			$num = SwatDB::exec($this->app->db, $sql);

			$message = new SwatMessage(sprintf(Store::ngettext(
				'The minor member status of one product has been changed.',
				'The minor member status of %s products has been changed.',
				$num), SwatString::numberFormat($num)));

			break;

		case 'products_enable_items':
			$region = $this->ui->getWidget('products_enable_region')->value;

			$num = SwatDB::queryOne($this->app->db, sprintf(
				'select count(id) from Item where product in (%s)',
				implode(',', $view->checked_items)));

			$sql = 'update ItemRegionBinding set enabled = %s
				where %s item in (select id from Item where product in (%s))';

			$region_sql = ($region > 0) ? 
				sprintf('region = %s and', $this->app->db->quote($region, 
					'integer')) :
				'';

			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(true, 'boolean'),
				$region_sql,
				implode(',', $view->checked_items)));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'%s item has been enabled.',
				'%s items have been enabled.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'products_disable_items':
			$region = $this->ui->getWidget('products_disable_region')->value;

			$num = SwatDB::queryOne($this->app->db, sprintf(
				'select count(id) from Item where product in (%s)',
				implode(',', $view->checked_items)));

			$sql = 'update ItemRegionBinding set enabled = %s
				where %s item in (select id from Item where product in (%s))';

			$region_sql = ($region > 0) ? 
				sprintf('region = %s and', $this->app->db->quote($region, 
					'integer')) :
				'';

			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(false, 'boolean'),
				$region_sql,
				implode(',', $view->checked_items)));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One item has been disabled.',
				'%s items have been disabled.', $num),
				SwatString::numberFormat($num)));

			break;
		}

		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}
	// {{{ private function processFeaturedProductActions()

	private function processFeaturedProductActions($view, $actions)
	{
		$num = count($view->checked_items);
		$message = null;

		switch ($actions->selected->id) {
		case 'featured_products_remove':
			$item_list = array();
			foreach ($view->checked_items as $item)
				$item_list[] = $this->app->db->quote($item, 'integer');

			SwatDB::query($this->app->db, sprintf('
				delete from CategoryFeaturedProductBinding
				where category = %s and product in (%s)',
				$this->app->db->quote($this->id, 'integer'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One featured product has been removed from this category.',
				'%s featured products have been removed from this category.',
				$num), SwatString::numberFormat($num)));
		}

		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	public function buildInternal()
	{
		parent::buildInternal();

		// setup the page layout
		$categories_frame = $this->ui->getWidget('categories_frame');
		$categories_frame->addStyleSheet(
			'packages/store/admin/styles/disabled-rows.css');

		if ($this->id === null) {
			// move the categories frame outside of the detail frame
			$categories_frame->parent->remove($categories_frame);
			$this->ui->getRoot()->add($categories_frame);
			$categories_frame->title = Store::_('Categories');
		} else {
			$categories_frame->classes[] = 'sub-categories';

			$this->ui->getWidget('create_category')->title =
				Store::_('New Sub-Category');

			$category_count = $this->getCategoryCount();
			$product_count = $this->getProductCount();

			// show the detail frame
			$details_frame = $this->ui->getWidget('details_frame');
			$details_frame->visible = true;

			$products_frame = $this->ui->getWidget('products_frame');
			$products_frame->visible = true;

			$this->ui->getWidget('products_change_order')->visible =
				($product_count > 1);

			$this->ui->getWidget('products_toolbar')->setToolLinkValues(
				$this->id);

			if ($category_count != 0) {
				// only show featured products if categories exist
				$featured_products_frame = $this->ui->getWidget(
					'featured_products_frame');

				$featured_products_frame->visible = true;

				$this->ui->getWidget('add_featured_product')->value = $this->id;
			}

			$this->ui->getWidget('category_change_order')->visible =
				($category_count > 1);

			$this->buildDetails();
		}

		$tool_value = ($this->id === null) ? '' : '?parent='.$this->id;
		$this->ui->getWidget('categories_toolbar')->setToolLinkValues(
			$tool_value);

		// setup the flydowns for status actions
		$statuses = $this->getItemStatuses();

		$this->ui->getWidget('products_status')->addOptionsByArray($statuses);

		foreach ($statuses as &$status)
			$status.= '…';

		$this->ui->getWidget('categories_status')->addOptionsByArray($statuses);

		// setup the flydowns for enabled/disabled actions
		$regions = SwatDB::getOptionArray($this->app->db, 'Region', 'title',
			'id');

		$regions[0] = Store::_('All Regions');

		$this->ui->getWidget('products_enable_region')->addOptionsByArray(
			$regions);

		$this->ui->getWidget('products_disable_region')->addOptionsByArray(
			$regions);

		foreach ($regions as &$item)
			$item.= '…';

		$this->ui->getWidget('categories_enable_region')->addOptionsByArray(
			$regions);

		$this->ui->getWidget('categories_disable_region')->addOptionsByArray(
			$regions);

		$this->buildMessages();
	}

	// }}}
	// {{{ protected function getItemStatuses()

	protected function getItemStatuses()
	{
		$class_map = StoreClassMap::instance();
		$item_class = $class_map->resolveClass('StoreItem');

		return call_user_func(array($item_class, 'getStatuses'));
	}
	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		switch ($view->id) {
		case 'categories_index_view':
			return $this->getCategoryTableStore($view);
		case 'products_index_view':
			return $this->getProductTableStore($view);
		case 'featured_products_index_view':
			return $this->getFeaturedProductTableStore($view);
		}
	}

	// }}}
	// {{{ private function getCategoryCount()

	/**
	 * use this query instead of the table store because of the
	 * where clause that filters by catalog.
	 */
	private function getCategoryCount()
	{
		return SwatDB::queryOne($this->app->db,
			sprintf('select count(id) from Category where parent = %s',
				$this->app->db->quote($this->id, 'integer')));
	}

	// }}}
	// {{{ private function getProductCount()

	/**
	 * use this query instead of the table store because of the
	 * where clause that filters by catalog.
	 */
	private function getProductCount()
	{
		$sql = sprintf('select count(product) from CategoryProductBinding
			where category = %s',
			 $this->app->db->quote($this->id, 'integer'));

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}

	// build phase - category details
	// {{{ private function buildDetails()

	private function buildDetails()
	{
		$this->ui->getWidget('details_toolbar')->setToolLinkValues($this->id);

		$category = $this->loadCategory();

		$this->buildCategoryDetails($category);
		$this->buildNavBar($category);

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Store::_('Category');
		$details_frame->subtitle = $category->title;
	}

	// }}}
	// {{{ private function loadCategory()

	private function loadCategory()
	{
		$class_map = StoreClassMap::instance();
		$category_class = $class_map->resolveClass('StoreCategory');

		$category = new $category_class();
		$category->setDatabase($this->app->db);

		if (!$category->load($this->id))
			throw new AdminNotFoundException(sprintf(
				Store::_('A category with an id of ‘%d’ does not exist.'),
				$this->id));

		return $category;
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar($category)
	{
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Product Categories'), 'Category'));

		if ($category->parent !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($category->parent));

			foreach ($navbar_rs as $row)
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry($category->title));
		$this->title = $category->title;
	}

	// }}}
	// {{{ private function buildCategoryDetails()

	private function buildCategoryDetails($category)
	{
		$this->buildImageDetails($category);

		$ds = new SwatDetailsStore($category);

		$ds->bodytext = SwatString::condense(SwatString::toXHTML(
			$category->bodytext), 120);

		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;
	}

	// }}}

	// build phase - table views
	// {{{ private function getCategoryTableStore()

	private function getCategoryTableStore($view)
	{
		$sql = 'select Category.id,
					Category.title,
					Category.shortname,
					Category.displayorder,
					Category.always_visible,
					CategoryChildCountView.child_count
				from Category
				left outer join CategoryChildCountView on
					CategoryChildCountView.category = Category.id
				where Category.parent %s %s
				order by %s';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view,
				'Category.displayorder, Category.title', 'Category'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		$this->setCategoryVisibility($store);

		if ($store->getRowCount() == 0) {
			$index_form = $this->ui->getWidget('categories_index_form');
			$index_form->visible = false;

			$change_order = $this->ui->getWidget('category_change_order');
			$change_order->visible = false;
		}

		return $store;
	}

	// }}}
	// {{{ protected function getProductTableStore()

	protected function getProductTableStore($view)
	{
		$sql = 'select Product.id,
					Product.title,
					Product.shortname,
					%s::integer as category_id,
					statuses.count_total as item_count,
					statuses.count_available,
					statuses.count_outofstock,
					statuses.count_disabled,
					CategoryProductBinding.minor
				from Product
					inner join CategoryProductBinding on
						Product.id = CategoryProductBinding.product
					inner join ProductItemCountByStatusView as statuses on
						statuses.product = Product.id
				where CategoryProductBinding.category = %s
					and Product.catalog in (%s)
				order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote($this->id, 'integer'),
			$this->ui->getWidget('catalog_switcher')->getSubQuery(),
			$this->getOrderByClause($view,
				'CategoryProductBinding.displayorder, Product.title',
				'Product'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		if ($store->getRowCount() == 0) {
			$index_form = $this->ui->getWidget('products_index_form');
			$index_form->visible = false;
		}

		return $store;
	}

	// }}}
	// {{{ private function setCategoryVisibility()

	private function setCategoryVisibility($store)
	{
		$sql = 'select category, sum(product_count) as product_count
			from CategoryProductCountByCatalogView
			where category in (select id from Category where parent %s %s)
				and catalog in (%s)
			group by category';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'),
			$this->ui->getWidget('catalog_switcher')->getSubQuery());

		$rs = SwatDB::query($this->app->db, $sql);
		$product_count = array();

		foreach ($rs as $row)
			$product_count[$row->category] = $row->product_count;


		$sql = 'select category
			from VisibleCategoryView 
			where category in (select id from Category where parent %s %s)';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);
		$visbile_categories = array();

		foreach ($rs as $row)
			$visible_categories[$row->category] = true;


		foreach ($store->getRows() as $row) {
			$row->product_count =
				isset($product_count[$row->id]) ? $product_count[$row->id] : 0;

			$row->currently_visible = (isset($visible_categories[$row->id]));
		}
	}

	// }}}
	// {{{ private function getFeaturedProductTableStore()

	private function getFeaturedProductTableStore($view)
	{
		$sql = 'select Product.id,
					Product.title,
					Product.shortname
				from Product
				inner join CategoryFeaturedProductBinding
					on Product.id = CategoryFeaturedProductBinding.product
				where CategoryFeaturedProductBinding.category = %s
					and Product.catalog in (%s)
				order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->ui->getWidget('catalog_switcher')->getSubQuery(),
			$this->getOrderByClause($view, 'Product.title', 'Product'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		if ($store->getRowCount() == 0) {
			$index_form = $this->ui->getWidget('featured_products_index_form');
			$index_form->visible = false;
		}

		return $store;
	}

	// }}}
	// {{{ private function buildImageDetails()

	private function buildImageDetails($category)
	{
		$this->ui->getWidget('image_toolbar')->setToolLinkValues($this->id);

		if ($category->image !== null) {
			$image = $this->ui->getWidget('image');

			// thumbnail width and height are always 60
			$image->image =
				'../images/categories/thumb/'.$category->image->id.'.jpg';

			$image->width = $category->image->thumb_width;
			$image->height = $category->image->thumb_height;
			$image->alt = sprintf(Store::_('Image of %s'), $category->title);

			$this->ui->getWidget('image_delete')->visible = true;
			$this->ui->getWidget('image_edit')->title =
				Store::_('Replace Image');
		}
	}

	// }}}
}

?>
