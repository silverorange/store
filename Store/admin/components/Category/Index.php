 <?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/StoreCatalogSwitcher.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/StoreItemStatusList.php';
require_once 'Store/dataobjects/StoreCategory.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreAttributeTypeWrapper.php';
require_once 'Store/dataobjects/StoreAttributeWrapper.php';

//TODO - move some of these into better locations
require_once 'Store/admin/components/Category/include/'.
	'StoreCategoryTableView.php';

require_once 'Store/admin/components/Category/include/'.
	'StoreCategoryTitleCellRenderer.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreProductStatusCellRenderer.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreProductTableView.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreProductTitleCellRenderer.php';

/**
 * Index page for Categories
 *
 * @package   Store
 * @copyright 2005-2010 silverorange
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

		$this->id = SiteApplication::initVar('id', null,
			SiteApplication::VAR_GET);

		$this->initCatalogSwitcher();
		$this->initAttributeList();

		$flydown = $this->ui->getWidget('item_minimum_quantity_group_flydown');
		$options = SwatDB::getOptionArray($this->app->db,
			'ItemMinimumQuantityGroup', 'title', 'id', 'title');

		$flydown->addOptionsByArray($options);
		$this->ui->getWidget('item_minimum_quantity_group')->visible =
			(count($options) > 0);

		$flydown = $this->ui->getWidget(
			'categories_item_minimum_quantity_group_flydown');

		$flydown->addOptionsByArray($options);
		$this->ui->getWidget(
			'categories_item_minimum_quantity_group')->visible =
				(count($options) > 0);
	}

	// }}}
	// {{{ private function initCatalogSwitcher()

	/**
	 * Builds the catalog switcher. Switcher does not get shown unless there is
	 * more than one catalog, as its not useful when there is only one.
	 */
	private function initCatalogSwitcher()
	{
		$this->ui->getWidget('catalog_switcher')->db = $this->app->db;

		$sql = 'select count(id) from Catalog';
		$catalog_count = SwatDB::queryOne($this->app->db, $sql);
		if ($catalog_count == 1)
			$this->ui->getWidget('catalog_switcher_form')->visible = false;
	}

	// }}}
	// {{{ private function initAttributeList()

	/**
	 * Builds the list of attributes using an image and a title
	 */
	private function initAttributeList()
	{
		$replicators = array();

		$attribute_types = SwatDB::query($this->app->db,
			'select * from attributetype order by shortname',
			SwatDBClassMap::get('StoreAttributeTypeWrapper'));

		foreach ($attribute_types as $type)
			$replicators[$type->id] = ucfirst($type->shortname);

		$fields = array(
			'product_attributes_form_field',
			'category_attributes_form_field',
			'product_remove_attributes_form_field',
			'category_remove_attributes_form_field',
		);

		foreach ($fields as $id)
			$this->ui->getWidget($id)->replicators = $replicators;
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$flush_memcache = false;

		// if nothing is selected, we have no actions to process
		if (count($view->getSelection()) == 0)
			return;

		switch ($view->id) {
		case 'categories_index_view':
			$flush_memcache = $this->processCategoryActions($view, $actions);
			break;

		case 'products_index_view':
			$flush_memcache = $this->processProductActions($view, $actions);
			break;

		case 'featured_products_index_view':
			$flush_memcache = $this->processFeaturedProductActions($view,
				$actions);

			break;

		case 'related_articles_index_view':
			$flush_memcache = $this->processRelatedArticles($view, $actions);
			break;
		}

		if ($flush_memcache === true && isset($this->app->memcache)) {
			$this->app->memcache->flushNs('product');
		}
	}

	// }}}
	// {{{ protected function processCategoryActions()

	protected function processCategoryActions($view, $actions)
	{
		$flush_memcache = false;

		switch ($actions->selected->id) {
		case 'categories_delete':
			$this->app->replacePage('Category/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

		case 'categories_remove_products':
			$this->app->replacePage('Category/RemoveProducts');
			$this->app->getPage()->setItems($view->getSelection());
			$this->app->getPage()->setCategory($this->id);
			break;

		case 'categories_change_status':
			$this->app->replacePage('Category/ChangeItemStatus');
			$this->app->getPage()->setItems($view->getSelection());
			$this->app->getPage()->setCategory($this->id);
			$this->app->getPage()->setStatus(
				$this->ui->getWidget('categories_status')->value);

			break;

		case 'categories_enable_items':
			$this->app->replacePage('Category/SetItemEnabled');
			$this->app->getPage()->setItems($view->getSelection());
			$this->app->getPage()->setCategory($this->id);
			$this->app->getPage()->setEnabled(true);
			$this->app->getPage()->setRegion(
				$this->ui->getWidget('categories_enable_region')->value);

			break;

		case 'categories_disable_items':
			$this->app->replacePage('Category/SetItemEnabled');
			$this->app->getPage()->setItems($view->getSelection());
			$this->app->getPage()->setCategory($this->id);
			$this->app->getPage()->setEnabled(false);
			$this->app->getPage()->setRegion(
				$this->ui->getWidget('categories_disable_region')->value);

			break;

		case 'categories_add_attributes':
			$attributes = $this->getAttributeArray('category_attributes');

			if (count($attributes) == 0)
				break;

			$product_array = $this->getProductsByCategories(
				$view->getSelection());

			if (count($product_array) === 0)
				break;

			if ($this->ui->getWidget('category_attributes_queue')->value ===
				true) {
				$this->app->replacePage('Product/QueueAttributes');
				$this->app->getPage()->setCategory($this->id);
				$this->app->getPage()->setItems($product_array);
				$this->app->getPage()->setAttributes($attributes);
				$this->app->getPage()->setAction('add');
				break;
			} else {
				$flush_memcache = $this->addProductAttributes($product_array,
					$attributes);
			}
			break;

		case 'categories_remove_attributes':
			$attributes = $this->getAttributeArray(
				'category_remove_attributes');

			if (count($attributes) == 0)
				break;

			$product_array = $this->getProductsByCategories(
				$view->getSelection());

			if (count($product_array) === 0)
				break;

			if ($this->ui->getWidget(
				'category_remove_attributes_queue')->value === true) {
				$this->app->replacePage('Product/QueueAttributes');
				$this->app->getPage()->setCategory($this->id);
				$this->app->getPage()->setItems($product_array);
				$this->app->getPage()->setAttributes($attributes);
				$this->app->getPage()->setAction('remove');
				break;
			} else {
				$flush_memcache = $this->removeProductAttributes($product_array,
					$attributes);
			}
			break;

		case 'categories_add_sale_discount' :
			$sale_discount = $this->ui->getWidget(
				'categories_sale_discount_flydown')->value;

			$product_array = $this->getProductsByCategories(
				$view->getSelection());

			if (count($product_array) === 0)
				break;

			$flush_memcache = $this->addSaleDiscount($product_array,
				$sale_discount);

			break;

		case 'categories_remove_sale_discount' :
			$product_array = $this->getProductsByCategories(
				$view->getSelection());

			if (count($product_array) === 0)
				break;

			$flush_memcache = $this->removeSaleDiscount($product_array);
			break;

		case 'categories_item_minimum_quantity_group' :
			$group = $this->ui->getWidget(
				'categories_item_minimum_quantity_group_flydown')->value;

			$product_array = $this->getProductsByCategories(
				$view->getSelection());

			if (count($product_array) === 0)
				break;

			$flush_memcache = $this->addItemMinimumQuantityGroup($product_array,
				$group);

			break;

		case 'categories_remove_item_minimum_quantity_group' :
			$product_array = $this->getProductsByCategories(
				$view->getSelection());

			if (count($product_array) === 0)
				break;

			$flush_memcache = $$this->removeItemMinimumQuantityGroup(
				$product_array);

			break;
		}

		return $flush_memcache;
	}

	// }}}
	// {{{ protected function getProductsByCategories()

	protected function getProductsByCategories($category_array)
	{
		$sql = sprintf('select descendant from
			getCategoryDescendants(null) where category in (%s)',
			SwatDB::implodeSelection($this->app->db,
				$category_array));

		$categories = SwatDB::query($this->app->db, $sql);
		$category_ids = array();

		foreach ($categories as $category)
			$category_ids[] = $this->app->db->quote(
				$category->descendant, 'integer');

		if (count($category_ids) > 0) {
			$product_array = SwatDB::getOptionArray($this->app->db,
				'CategoryProductBinding', 'product', 'product', null,
				sprintf('category in (%s)', implode(',', $category_ids)));
		}

		return $product_array;
	}

	// }}}
	// {{{ protected function processProductActions()

	protected function processProductActions($view, $actions)
	{
		$flush_memcache = false;
		$num = count($view->getSelection());
		$message = null;

		$item_list = array();
		foreach ($view->getSelection() as $item)
			$item_list[] = $this->app->db->quote($item, 'integer');

		switch ($actions->selected->id) {
		case 'products_delete':
			$this->app->replacePage('Product/Delete');
			$this->app->getPage()->setItems($view->getSelection());
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

			$flush_memcache = true;

			break;

		case 'products_change_status' :
			$new_status = $this->ui->getWidget('products_status')->value;

			$num = SwatDB::queryOne($this->app->db, sprintf(
				'select count(id) from Item where product in (%s)',
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			SwatDB::updateColumn($this->app->db, 'Item', 'integer:status',
				$new_status, 'product', $item_list);

			$message = new SwatMessage(sprintf(Store::ngettext(
				'The status of one item has been changed.',
				'The status of %s items has been changed.', $num),
				SwatString::numberFormat($num)));

			$flush_memcache = true;

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
				SwatDB::implodeSelection($this->app->db, $view->getSelection()),
				$this->app->db->quote($this->id, 'integer'));

			$num = SwatDB::exec($this->app->db, $sql);

			$message = new SwatMessage(sprintf(Store::ngettext(
				'The minor member status of one product has been changed.',
				'The minor member status of %s products has been changed.',
				$num), SwatString::numberFormat($num)));

			$flush_memcache = true;

			break;

		case 'products_enable_items':
			$region = $this->ui->getWidget('products_enable_region')->value;

			$num = SwatDB::queryOne($this->app->db, sprintf(
				'select count(id) from Item where product in (%s)',
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			$sql = 'update ItemRegionBinding set enabled = %s
				where price is not null
					and %s item in (select id from Item where product in (%s))';

			$region_sql = ($region > 0) ?
				sprintf('region = %s and', $this->app->db->quote($region,
					'integer')) :
				'';

			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(true, 'boolean'),
				$region_sql,
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'%s item has been enabled.',
				'%s items have been enabled.', $num),
				SwatString::numberFormat($num)));

			$flush_memcache = true;

			break;

		case 'products_disable_items':
			$region = $this->ui->getWidget('products_disable_region')->value;

			$num = SwatDB::queryOne($this->app->db, sprintf(
				'select count(id) from Item where product in (%s)',
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			$sql = 'update ItemRegionBinding set enabled = %s
				where %s item in (select id from Item where product in (%s))';

			$region_sql = ($region > 0) ?
				sprintf('region = %s and', $this->app->db->quote($region,
					'integer')) :
				'';

			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(false, 'boolean'),
				$region_sql,
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection())));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One item has been disabled.',
				'%s items have been disabled.', $num),
				SwatString::numberFormat($num)));

			$flush_memcache = true;

			break;

		case 'products_add_attributes' :
			$attributes = $this->getAttributeArray('product_attributes');

			if (count($attributes) == 0)
				break;

			if ($this->ui->getWidget('product_attributes_queue')->value ===
				true) {
				$this->app->replacePage('Product/QueueAttributes');
				$this->app->getPage()->setCategory($this->id);
				$this->app->getPage()->setItems($view->getSelection());
				$this->app->getPage()->setAttributes($attributes);
				$this->app->getPage()->setAction('add');
				break;
			} else {
				$flush_memcache = $this->addProductAttributes(
					$view->getSelection(), $attributes);
			}
			break;

		case 'products_remove_attributes' :
			$attributes = $this->getAttributeArray('product_remove_attributes');

			if (count($attributes) == 0)
				break;

			if ($this->ui->getWidget(
				'product_remove_attributes_queue')->value === true) {
				$this->app->replacePage('Product/QueueAttributes');
				$this->app->getPage()->setCategory($this->id);
				$this->app->getPage()->setItems($view->getSelection());
				$this->app->getPage()->setAttributes($attributes);
				$this->app->getPage()->setAction('remove');
				break;
			} else {
				$flush_memcache = $this->addProductAttributes(
					$view->getSelection(), $attributes);
			}
			break;

		case 'products_add_sale_discount' :
			$sale_discount = $this->ui->getWidget(
				'products_sale_discount_flydown')->value;

			$flush_memcache = $this->addSaleDiscount($view->getSelection(),
				$sale_discount);

			break;

		case 'products_remove_sale_discount' :
			$flush_memcache = $this->removeSaleDiscount($view->getSelection());

			break;

		case 'item_minimum_quantity_group' :
			$group = $this->ui->getWidget(
				'item_minimum_quantity_group_flydown')->value;

			$flush_memcache = $this->addItemMinimumQuantityGroup(
				$view->getSelection(), $group);

			break;

		case 'remove_item_minimum_quantity_group' :
			$flush_memcache = $this->removeItemMinimumQuantityGroup(
				$view->getSelection());

			break;
		}

		if ($message !== null)
			$this->app->messages->add($message);

		return $flush_memcache;
	}

	// }}}
	// {{{ private function processFeaturedProductActions()

	private function processFeaturedProductActions($view, $actions)
	{
		$flush_memcache = false;
		$num = count($view->getSelection());
		$message = null;

		switch ($actions->selected->id) {
		case 'featured_products_remove':
			$item_list = array();
			foreach ($view->getSelection() as $item)
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

			$flush_memcache = true;
		}

		if ($message !== null)
			$this->app->messages->add($message);

		return $flush_memcache;
	}

	// }}}
	// {{{ private function processRelatedArticles()

	private function processRelatedArticles($view, $actions)
	{
		$flush_memcache = false;
		$num = count($view->getSelection());
		$message = null;

		switch ($actions->selected->id) {
		case 'related_article_remove':
			$item_list = array();
			foreach ($view->getSelection() as $item)
				$item_list[] = $this->app->db->quote($item, 'integer');

			$num = SwatDB::exec($this->app->db, sprintf('
				delete from ArticleCategoryBinding
				where category = %s and article in (%s)',
				$this->app->db->quote($this->id, 'integer'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One related article has been removed from this category.',
				'%s related articles have been removed from this category.',
				$num), SwatString::numberFormat($num)));

			$flush_memcache = true;
		}

		if ($message !== null)
			$this->app->messages->add($message);

		return $flush_memcache;
	}

	// }}}
	// {{{ protected function getAttributeArray()

	protected function getAttributeArray($widget_title, $form_field_title = '')
	{
		$attribute_array = array();
		if ($form_field_title == '')
			$form_field_title = $widget_title.'_form_field';

		$attributes_field = $this->ui->getWidget($form_field_title);

		foreach ($attributes_field->replicators as $id => $title) {
			foreach ($attributes_field->getWidget($widget_title, $id)->values as
				$value) {
				$attribute_array[] = $this->app->db->quote($value, 'integer');
			}
		}

		return $attribute_array;
	}

	// }}}
	// {{{ private function addProductAttributes()

	private function addProductAttributes($products, $attributes)
	{
		$flush_memcache = false;

		if (count($products) == 0 || count($attributes) == 0)
			return $flush_memcache;

		$product_array = array();
		foreach ($products as $product)
			$product_array[] = $this->app->db->quote($product, 'integer');

		$sql = sprintf('delete from ProductAttributeBinding
			where product in (%s) and attribute in (%s)',
			implode(',', $product_array),
			implode(',', $attributes));

		$delete_count = SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into ProductAttributeBinding
			(product, attribute)
			select Product.id, Attribute.id
			from Product cross join Attribute
			where Product.id in (%s) and Attribute.id in (%s)',
			implode(',', $product_array),
			implode(',', $attributes));

		$add_count = SwatDB::exec($this->app->db, $sql);

		if ($add_count != $delete_count) {
			$flush_memcache = true;
		}

		// TODO: we could have better messages for this that gave accurate
		// numbers of products and attributes updated, versus just the number of
		// each passed in.

		// You unfortunately can't nest ngettext calls. Nor does there appear to
		// be a better way to do a sentence with multiple plural options.
		if (count($attributes) == 1) {
			$message_text = Store::ngettext(
				'One product has had one product attribute added.',
				'%1$s products have had one product attribute added.',
				count($products));
		} else {
			$message_text = Store::ngettext(
				'One product has had %2$s product attributes added.',
				'%1$s products have had %2$s product attributes added.',
				count($products));
		}

		$message = new SwatMessage(sprintf($message_text,
			SwatString::numberFormat(count($products)),
			SwatString::numberFormat(count($attributes))));

		$this->app->messages->add($message);

		return $flush_memcache;
	}

	// }}}
	// {{{ private function removeProductAttributes()

	private function removeProductAttributes($products, $attributes)
	{
		$flush_memcache = false;

		if (count($products) == 0 || count($attributes) == 0)
			return $flush_memcache;

		$product_array = array();
		foreach ($products as $product)
			$product_array[] = $this->app->db->quote($product, 'integer');

		$sql = sprintf('delete from ProductAttributeBinding
			where product in (%s) and attribute in (%s)',
			implode(',', $product_array),
			implode(',', $attributes));

		$count = SwatDB::exec($this->app->db, $sql);

		if ($count > 0) {
			$flush_memcache = true;

			// TODO: we could have better messages for this that gave accurate
			// numbers of products attributes removed, versus just the number
			// of each passed in.

			// You unfortunately can't nest ngettext calls. Nor does there
			// appear to be a better way to do a sentence with multiple plural
			// options.
			if (count($attributes) == 1) {
				$message_text = Store::ngettext(
					'One product has had one product attribute removed.',
					'%1$s products have had one product attribute removed.',
					count($products));
			} else {
				$message_text = Store::ngettext(
					'One product has had %2$s product attributes removed.',
					'%1$s products have had %2$s product attributes removed.',
					count($products));
			}

			$message = new SwatMessage(sprintf($message_text,
				SwatString::numberFormat(count($products)),
				SwatString::numberFormat(count($attributes))));
		} else {
			$message = new SwatMessage(Store::_(
				'None of the products selected had attributes to remove.'));
		}

		$this->app->messages->add($message);

		return $flush_memcache;
	}

	// }}}
	// {{{ private function addSaleDiscount()

	private function addSaleDiscount($products, $sale_discount)
	{
		$flush_memcache = false;
		if (count($products) == 0 || $sale_discount === null)
			return $flush_memcache;

		$product_array = array();

		foreach ($products as $product)
			$product_array[] = $this->app->db->quote($product, 'integer');

		$num = SwatDB::queryOne($this->app->db, sprintf(
			'select count(id) from Item where product in (%s)',
			implode(', ', $product_array)));

		SwatDB::updateColumn($this->app->db, 'Item',
			'integer:sale_discount', $sale_discount, 'product',
			$product_array);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'A sale discount has been applied to one item.',
			'A sale discount has been applied to %s items.', $num),
			SwatString::numberFormat($num)));

		$this->app->messages->add($message);
		$flush_memcache = true;

		return $flush_memcache;
	}

	// }}}
	// {{{ private function removeSaleDiscount()

	private function removeSaleDiscount($products)
	{
		$flush_memcache = false;
		if (count($products) == 0)
			return $flush_memcache;

		$product_array = array();

		foreach ($products as $product)
			$product_array[] = $this->app->db->quote($product, 'integer');

		$num = SwatDB::queryOne($this->app->db, sprintf(
			'select count(id) from Item where product in (%s)
			and sale_discount is not null',
			implode(', ', $product_array)));

		if ($num > 0) {
			SwatDB::updateColumn($this->app->db, 'Item',
				'integer:sale_discount', null, 'product', $product_array);

			SwatDB::exec($this->app->db, sprintf(
				'update ItemRegionBinding set sale_discount_price = null
				where item in (select id from Item where product in (%s))',
				implode(', ', $product_array)));

			$message = new SwatMessage(sprintf(Store::ngettext(
				'A sale discount has been removed from one item.',
				'A sale discount has been removed from %s items.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			$flush_memcache = true;
		} else {
			$this->app->messages->add(new SwatMessage(Store::_(
				'None of the items selected had a sale discount.')));
		}

		return $flush_memcache;
	}

	// }}}
	// {{{ private function addItemMinimumQuantityGroup()

	private function addItemMinimumQuantityGroup($products, $group)
	{
		$flush_memcache = false;
		if (count($products) == 0 || $group === null)
			return $flush_memcache;

		$product_array = array();

		foreach ($products as $product)
			$product_array[] = $this->app->db->quote($product, 'integer');

		$num = SwatDB::queryOne($this->app->db, sprintf(
			'select count(id) from Item where product in (%s)',
			implode(', ', $product_array)));

		SwatDB::updateColumn($this->app->db, 'Item',
			'integer:minimum_quantity_group', $group, 'product',
			$product_array);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'An item miniumum quantity sale group has been applied '.
				'to one item.',
			'An item miniumum quantity sale group has been applied to '.
				'%s items.', $num),
			SwatString::numberFormat($num)));

		$this->app->messages->add($message);
		$flush_memcache = true;
		return $flush_memcache;
	}

	// }}}
	// {{{ private function removeItemMinimumQuantityGroup()

	private function removeItemMinimumQuantityGroup($products)
	{
		$flush_memcache = false;
		if (count($products) == 0)
			return $flush_memcache;

		$product_array = array();

		foreach ($products as $product)
			$product_array[] = $this->app->db->quote($product, 'integer');

		$num = SwatDB::queryOne($this->app->db, sprintf(
			'select count(id) from Item where product in (%s)
			and minimum_quantity_group is not null',
			implode(', ', $product_array)));

		if ($num > 0) {
			SwatDB::updateColumn($this->app->db, 'Item',
				'integer:minimum_quantity_group', null, 'product',
				$product_array);

			$message = new SwatMessage(sprintf(Store::ngettext(
				'A item miniumum quantity sale group has been '.
					'removed from one item.',
				'A item miniumum quantity sale group has been '.
					'removed from %s items.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			$flush_memcache = true;
		} else {
			$this->app->messages->add(new SwatMessage(Store::_(
				'None of the items selected had a item miniumum '.
				'quantity sale group.')));
		}

		return $flush_memcache;
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

			$this->ui->getWidget('related_articles_frame')->visible = true;
			$this->ui->getWidget('related_articles_toolbar')->setToolLinkValues(
				$this->id);

			$this->buildDetails();
		}

		$tool_value = ($this->id === null) ? '' : '?parent='.$this->id;
		$this->ui->getWidget('categories_toolbar')->setToolLinkValues(
			$tool_value);

		$this->buildActions();
		$this->buildMessages();
	}

	// }}}
	// {{{ protected function buildActions()

	public function buildActions()
	{
		// setup the flydowns for status actions
		$products_status = $this->ui->getWidget('products_status');
		$categories_status = $this->ui->getWidget('categories_status');
		foreach ($this->getItemStatuses() as $status) {
			$products_status->addOption(
				new SwatOption($status->id, $status->title));

			$categories_status->addOption(
				new SwatOption($status->id, $status->title.'…'));
		}

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

		// attributes
		$this->buildAttributes('product_attributes_form_field',
			'product_attributes');

		$this->buildAttributes('category_attributes_form_field',
			'category_attributes');

		$this->buildAttributes('product_remove_attributes_form_field',
			'product_remove_attributes');

		$this->buildAttributes('category_remove_attributes_form_field',
			'category_remove_attributes');

		// sale discounts
		$sale_discounts = SwatDB::getOptionArray($this->app->db,
			'SaleDiscount', 'title', 'id', 'title');

		$flydown = $this->ui->getWidget('categories_sale_discount_flydown');
		$flydown->addOptionsByArray($sale_discounts);

		$flydown = $this->ui->getWidget('products_sale_discount_flydown');
		$flydown->addOptionsByArray($sale_discounts);
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
		case 'categories_index_view':
			return $this->getCategoryTableModel($view);
		case 'products_index_view':
			return $this->getProductTableModel($view);
		case 'featured_products_index_view':
			return $this->getFeaturedProductTableModel($view);
		case 'related_articles_index_view':
			return $this->getRelatedArticleTableModel($view);
		}
	}

	// }}}
	// {{{ protected function getItemStatuses()

	protected function getItemStatuses()
	{
		return StoreItemStatusList::statuses();
	}

	// }}}
	// {{{ protected function displayAttribute()

	protected function displayAttribute(StoreAttribute $attribute)
	{
		$attribute->display();
	}

	// }}}
	// {{{ private function buildAttributes()

	private function buildAttributes($form_field_id, $check_list_id)
	{
		$sql = 'select id, shortname, title, attribute_type from Attribute
			order by attribute_type, displayorder, id';

		$attributes = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreAttributeWrapper'));

		$attributes_field = $this->ui->getWidget($form_field_id);

		foreach ($attributes as $attribute) {
			ob_start();
			$this->displayAttribute($attribute);
			$option = ob_get_clean();

			$attributes_field->getWidget($check_list_id,
				$attribute->attribute_type->id)->addOption(
					$attribute->id, $option, 'text/xml');
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
	// {{{ protected function buildDetails()

	protected function buildDetails()
	{
		$this->ui->getWidget('details_toolbar')->setToolLinkValues($this->id);

		$category = $this->loadCategory();

		$this->buildCategoryDetails($category);
		$this->buildCategoryNavBar($category);

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Store::_('Category');
		$details_frame->subtitle = $category->title;
	}

	// }}}
	// {{{ protected function buildCategoryDetails()

	protected function buildCategoryDetails($category)
	{
		$this->buildImageDetails($category);

		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $this->getCategoryDetailsStore($category);
	}

	// }}}
	// {{{ protected function getCategoryDetailsStore()

	protected function getCategoryDetailsStore(StoreCategory $category)
	{
		$ds = new SwatDetailsStore($category);

		$ds->bodytext = SwatString::condense(SwatString::toXHTML(
			$category->bodytext), 120);

		return $ds;
	}

	// }}}
	// {{{ private function loadCategory()

	private function loadCategory()
	{
		$category_class = SwatDBClassMap::get('StoreCategory');

		$category = new $category_class();
		$category->setDatabase($this->app->db);

		if (!$category->load($this->id))
			throw new AdminNotFoundException(sprintf(
				Store::_('A category with an id of ‘%d’ does not exist.'),
				$this->id));

		return $category;
	}

	// }}}
	// {{{ private function buildCategoryNavBar()

	private function buildCategoryNavBar($category)
	{
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Product Categories'), 'Category'));

		if ($category->getInternalValue('parent') !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar',
					array($category->getInternalValue('parent')));

			foreach ($navbar_rs as $row)
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry($category->title));
		$this->title = $category->title;
	}

	// }}}

	// build phase - table views
	// {{{ protected function getCategoryTableModel()

	protected function getCategoryTableModel(SwatTableView $view)
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

		$rs = SwatDB::query($this->app->db, $sql);

		$this->setCategoryVisibility($rs);

		if (count($rs) == 0) {
			$index_form = $this->ui->getWidget('categories_index_form');
			$index_form->visible = false;

			$change_order = $this->ui->getWidget('category_change_order');
			$change_order->visible = false;
		}

		return $rs;
	}

	// }}}
	// {{{ protected function getProductTableModel()

	protected function getProductTableModel(SwatTableView $view)
	{
		$sql = 'select Product.id,
					Product.title,
					Product.shortname,
					%s::integer as category_id,
					ProductItemCountByStatusView.*,
					CategoryProductBinding.minor
				from Product
					inner join CategoryProductBinding on
						Product.id = CategoryProductBinding.product
					inner join ProductItemCountByStatusView on
						ProductItemCountByStatusView.product = Product.id
				where CategoryProductBinding.category = %s
					and Product.catalog in (%s)
				order by %s';

		// only products from the currently selected catalogue(s) are selected
		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote($this->id, 'integer'),
			$this->ui->getWidget('catalog_switcher')->getSubQuery(),
			$this->getOrderByClause($view,
				'CategoryProductBinding.displayorder, Product.title',
				'Product'));

		$rs = SwatDB::query($this->app->db, $sql);

		$this->setProductVisibility($rs);

		if (count($rs) == 0) {
			$index_form = $this->ui->getWidget('products_index_form');
			$index_form->visible = false;
		}

		return $rs;
	}

	// }}}
	// {{{ protected function setCategoryVisibility()

	protected function setCategoryVisibility(SwatTableModel $model)
	{
		// get category product count (depends on current catalogue)
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

		/*
		 * Get category visibility (does not depend on current catalogue).
		 *
		 * Not using the VisibleCategoryView here because it depends on a cache
		 * table that may not be updated yet.
		 */
		$sql = 'select v.category,
				sum(v.product_count) as product_count,
				Category.always_visible
			from CategoryVisibleProductCountByRegionView as v
				inner join Category on Category.id = v.category
			where Category.parent %s %s
			group by category, always_visible';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		$visbile_categories = array();
		foreach ($rs as $row) {
			if ($row->product_count > 0 || $row->always_visible)
				$visible_categories[$row->category] = true;
		}

		// set product count and visibility for categories
		foreach ($model as $row) {
			$row->product_count = (isset($product_count[$row->id])) ?
				$product_count[$row->id] : 0;

			$row->currently_visible = (isset($visible_categories[$row->id]));
		}
	}

	// }}}
	// {{{ protected function setProductVisibility()

	protected function setProductVisibility(SwatTableModel $model)
	{
		if (count($model) > 0) {
			// get product visibility (does not depend on current catalogue)
			$quoted_ids = array();
			foreach ($model as $row)
				$quoted_ids[] = $this->app->db->quote($row->id, 'integer');

			$sql = sprintf('select product from VisibleProductView
				where product in (%s)',
				implode(', ', $quoted_ids));

			$rs = SwatDB::query($this->app->db, $sql);
			$visbile_products = array();

			foreach ($rs as $row)
				$visible_products[$row->product] = true;

			// set visibility for products
			foreach ($model as $row)
				$row->currently_visible = (isset($visible_products[$row->id]));
		}
	}

	// }}}
	// {{{ private function getFeaturedProductTableModel()

	private function getFeaturedProductTableModel(SwatTableView $view)
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

		$rs = SwatDB::query($this->app->db, $sql);

		if (count($rs) == 0) {
			$index_form = $this->ui->getWidget('featured_products_index_form');
			$index_form->visible = false;
		}

		return $rs;
	}

	// }}}
	// {{{ private function getRelatedArticleTableModel()

	private function getRelatedArticleTableModel(SwatTableView $view)
	{
		$sql = 'select Article.id,
					Article.title
				from Article
				inner join ArticleCategoryBinding
					on Article.id = ArticleCategoryBinding.article
				where ArticleCategoryBinding.category = %s
				order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view, 'Article.title', 'Article'));

		$rs = SwatDB::query($this->app->db, $sql);

		if (count($rs) == 0) {
			$index_form = $this->ui->getWidget('related_articles_index_form');
			$index_form->visible = false;
		}

		return $rs;
	}

	// }}}
	// {{{ private function buildImageDetails()

	private function buildImageDetails($category)
	{
		$this->ui->getWidget('image_toolbar')->setToolLinkValues($this->id);

		if ($category->image !== null) {
			$image = $this->ui->getWidget('image');
			$image->image  = $category->image->getUri('thumb', '../');
			$image->width  = $category->image->getWidth('thumb');
			$image->height = $category->image->getHeight('thumb');
			$image->alt    = sprintf(Store::_('Image of %s'), $category->title);

			$this->ui->getWidget('image_delete')->visible = true;
			$this->ui->getWidget('image_edit')->title =
				Store::_('Replace Image');
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/admin/styles/store-category-index.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
