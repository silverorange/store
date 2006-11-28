<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatMoneyEntry.php';
require_once 'Swat/SwatFlydown.php';
require_once 'Swat/SwatNavBar.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';
require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/admin/components/Product/include/StoreItemTableView.php';
require_once 'Store/admin/components/Product/include/StoreItemGroupGroup.php';
require_once 'Store/admin/components/Product/include/StoreItemGroupAction.php';
require_once
	'Store/admin/components/Item/include/StoreItemStatusCellRenderer.php';

require_once 
	'Store/admin/components/Product/include/StoreItemDiscountCellRenderer.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreItemRegionPriceCellRenderer.php';

/**
 * Index page for Products
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductDetails extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Product/details.xml';

	// }}}
	// {{{ private properties

	private $id;
	private $category_id;

	/**
	 * Cache of regions used by queryRegions()
	 *
	 * @var RegionsWrapper
	 */
	private $regions = null;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->ui->getRoot()->addStyleSheet(
			'packages/store/admin/styles/store-product-details-page.css');

		$this->ui->getRoot()->addStyleSheet(
			'packages/store/admin/styles/store-image-preview.css');
	
		$this->id = SiteApplication::initVar('id');
		$this->category_id = SiteApplication::initVar('category', null,
			SiteApplication::VAR_GET);

		$this->ui->getWidget('item_group')->db = $this->app->db;
		$this->ui->getWidget('item_group')->product_id = $this->id;

		$regions = $this->queryRegions();
		$view = $this->ui->getWidget('items_view');

		// add dynamic columns to items view
		$this->appendPriceColumns($view, $regions);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$related_products_form = $this->ui->getWidget('related_products_form');
		$related_products_view = $this->ui->getWidget('related_products_view');
		if ($related_products_form->isProcessed() &&
			count($related_products_view->checked_items) != 0)
			$this->processRelatedProducts($related_products_view);

		// add new items
		if ($this->ui->getWidget('index_actions')->selected !== null &&
			$this->ui->getWidget('index_actions')->selected->id == 'add') {

			$this->addNewItems();
		}
	}

	// }}}
	// {{{ private function processRelatedProducts()

	private function processRelatedProducts($view)
	{
		$this->app->replacePage('Product/RelatedProductDelete');
		$this->app->getPage()->setItems($view->checked_items);
		$this->app->getPage()->setId($this->id);
		$this->app->getPage()->setCategory($this->category_id);
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Item/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;

		case 'change_group':
			$item_group_action = $this->ui->getWidget('item_group');
			$msg = $item_group_action->processAction($view->checked_items);
			$this->app->messages->add($msg);	
			break;

		case 'change_status' :
			$new_status = $this->ui->getWidget('status')->value;
			$this->changeStatus($view, $new_status);
			break;

		case 'enable':
			$region = $this->ui->getWidget('enable_region')->value;

			$sql = 'update ItemRegionBinding set enabled = %s
				where %s item in (%s)';

			$region_sql = ($region > 0) ? 
				sprintf('region = %s and', $this->app->db->quote($region, 
					'integer')) : '';

			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(true, 'boolean'),
				$region_sql,
				implode(',', $view->checked_items)));

			$num = count($view->checked_items);

			$msg = new SwatMessage(sprintf(Store::ngettext(
				'One item has been enabled.',
				'%d items have been enabled.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($msg);
			break;

		case 'disable':
			$region = $this->ui->getWidget('disable_region')->value;

			$sql = 'update ItemRegionBinding set enabled = %s
				where %s item in (%s)';

			$region_sql = ($region > 0) ? 
				sprintf('region = %s and', $this->app->db->quote($region, 
					'integer')) : '';

			SwatDB::exec($this->app->db, sprintf($sql,
				$this->app->db->quote(false, 'boolean'),
				$region_sql,
				implode(',', $view->checked_items)));

			$num = count($view->checked_items);

			$msg = new SwatMessage(sprintf(Store::ngettext(
				'One item has been disabled.',
				'%d items have been disabled.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($msg);
			break;
		}
	}

	// }}}
	// {{{ private function changeStatus()
	protected function changeStatus(SwatTableView $view, $status)
	{
		$num = count($view->checked_items);

		SwatDB::updateColumn($this->app->db, 'Item', 'integer:status', $status,
			'id', $view->checked_items);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'The status of one item has been changed.',
			'The status of %d items has been changed.', $num),
			SwatString::numberFormat($num)));

		$this->app->messages->add($msg);
	}
	// }}}
	// {{{ private function addNewItems()

	private function addNewItems()
	{
		$sql = sprintf('select catalog from Product where id = %s',
			$this->app->db->quote($this->id, 'integer'));

		$catalog = SwatDB::queryOne($this->app->db, $sql);

		$view = $this->ui->getWidget('items_view');
		$input_row = $view->getRow('input_row');

		$regions = $this->queryRegions();

		$fields = array(
			'text:sku',
			'text:description',
			'integer:product',
		);

		$item_region_fields = array(
			'integer:item',
			'integer:region',
			'decimal:price',
			'boolean:enabled',
		);

		if ($this->validateItemRows($input_row, $catalog)) {
			$new_skus = array();
			$replicators = $input_row->getReplicators();
			foreach ($replicators as $replicator_id) {
				if (!$input_row->rowHasMessage($replicator_id)) {
					$sku = $input_row->getWidget(
						'sku', $replicator_id)->value;

					$description = $input_row->getWidget(
						'description', $replicator_id)->value;

					// Create new item
					$values = array(
						'sku' => $sku,
						'description' => $description,
						'product' => $this->id,
					);

					$item_id = SwatDB::insertRow($this->app->db, 'Item',
						$fields, $values, 'id');

					foreach ($regions as $region) {
						$price = $input_row->getWidget('price_'.$region->id, 
							$replicator_id);

						if ($price->getState() !== null) {
							// Create new item_region binding
							$item_region_values = array('item' => $item_id,
								'region' => $region->id, 
								'price' => $price->getState(),
								'enabled' => true);

							SwatDB::insertRow($this->app->db,
								'ItemRegionBinding', $item_region_fields,
								$item_region_values);
						}
					}

					// remove the row after we entered it
					$input_row->removeReplicatedRow($replicator_id);

					$new_skus[] = SwatString::minimizeEntities($sku);
				}
			}

			if (count($new_skus) == 1) {
				$msg = new SwatMessage(sprintf(Store::_('“%s” has been added.'),
					$new_skus[0]));

				$this->app->messages->add($msg);
			} elseif (count($new_skus) > 1) {
				$sku_list = '<ul><li>'.implode('</li><li>', $new_skus).
					'</li></ul>';

				$msg = new SwatMessage(
					Store::_('The following items have been added:'));

				$msg->secondary_content = $sku_list;
				$msg->content_type = 'text/xml';
				$this->app->messages->add($msg);
			}
		} else {
			$msg = new SwatMessage(Store::_('There was a problem adding the '.
				'item(s). Please check the highlighted fields below.'),
				SwatMessage::ERROR);

			$this->app->messages->add($msg);
		}
	}

	// }}}
	// {{{ private function validateItemRows()

	private function validateItemRows($input_row, $catalog)
	{
		$validate = true;
		$replicators = $input_row->getReplicators();

		foreach ($replicators as $replicator_id) {
			// validate sku
			$sku_widget = $input_row->getWidget('sku', $replicator_id);
			if (!StoreItem::validateSku($this->app->db,
				$sku_widget->getState(), $catalog, $this->id)) {
				$sku_widget->addMessage(new SwatMessage(
					Store::_('%s must be unique amongst all catalogs unless '.
					'catalogs are clones of each other.')));

				$validate = false;
			}
		}

		return $validate;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildProduct();
		$this->buildItems();
		$this->buildRelatedProducts();
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		switch ($view->id) {
			case 'items_view':
				return $this->getItemsTableStore($view);
			case  'related_products_view':
				return $this->getRelatedProductsTableStore($view);
		}
	}

	// }}}

	// build phase - product details
	// {{{ private function buildProduct()

	private function buildProduct()
	{
		$product = $this->loadProduct();

		$this->buildProductDetails($product);
		$this->buildImageDetails($product);

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Store::_('Product');
		$details_frame->subtitle = $product->title;
		$this->title = $product->title;

		$toolbar = $this->ui->getWidget('details_toolbar');

		if ($this->category_id === null) {
			$toolbar->setToolLinkValues($this->id);
		} else {
			foreach ($toolbar->getToolLinks() as $tool_link) {
				if ($tool_link->id != 'view_in_store') {
					$tool_link->link.= '&category=%s';
					$tool_link->value = array($this->id, $this->category_id);
				}
			}
		}

		$this->buildViewInStoreToolLinks($product);
		$this->buildNavBar($product);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar($product)
	{
		if ($this->category_id !== null) {
			// use category navbar
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry)
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry($product->title));
	}

	// }}}
	// {{{ private function loadProduct()

	private function loadProduct()
	{
		$class_map = StoreClassMap::instance();
		$product_class = $class_map->resolveClass('StoreProduct');
		$product = new $product_class();
		$product->setDatabase($this->app->db);

		if (!$product->load($this->id))
			throw new AdminNotFoundException(sprintf(
				Store::_('A product with an id of ‘%d’ does not exist.'),
				$this->id));

		return $product;
	}

	// }}}
	// {{{ protected function buildProductDetails()

	protected function buildProductDetails($product)
	{
		$ds = new SwatDetailsStore($product);

		ob_start();
		$this->displayCategories($product->categories);
		$ds->categories = ob_get_clean();

		ob_start();
		$this->displayCategories($product->featured_categories);
		$ds->featured_categories = ob_get_clean();

		// format the bodytext
		$ds->bodytext = SwatString::condense(SwatString::toXHTML(
			$product->bodytext));

		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;
	}

	// }}}
	// {{{ private function buildViewInStoreToolLinks()

	private function buildViewInStoreToolLinks($product)
	{
		$some_category = $product->categories->getFirst();
		if ($some_category !== null) {
			$prototype_tool_link = $this->ui->getWidget('view_in_store');
			$toolbar = $prototype_tool_link->parent;
			$toolbar->remove($prototype_tool_link);
			$path = $some_category->path;

			foreach ($this->queryRegions() as $region) {
				$locale = $region->getFirstLocale();
				if ($locale !== null) {
					$tool_link = clone $prototype_tool_link;
					$tool_link->id.= '_'.$region->id;
					$tool_link->value = $locale->getURLLocale();
					$tool_link->value.= 'store/'.$path.'/'.$product->shortname;
					$tool_link->title.= sprintf(' (%s)', $region->title);
					$toolbar->packEnd($tool_link);
				}
			}
		}
	}

	// }}}
	// {{{ private function buildImageDetails()

	private function buildImageDetails($product)
	{
		$this->ui->getWidget('image_toolbar')->setToolLinkValues($this->id);

		if ($this->category_id !== null) {
			// set image edit link
			$image_edit = $this->ui->getWidget('image_edit');
			$image_edit->link = 'Product/ImageEdit?product=%s&category=%s';
			$image_edit->value = array($this->id, $this->category_id);

			// set image delete link
			$image_delete = $this->ui->getWidget('image_delete');
			$image_delete->link = 'Product/ImageDelete?id=%s&category=%s';
			$image_delete->value = array($this->id, $this->category_id);
		}

		if ($product->primary_image === null) {
			return;
		} else {
			$image = $this->ui->getWidget('image');
			$image->image = 
				'../images/products/thumb/'.$product->primary_image->id.'.jpg';

			$image->width = $product->primary_image->thumb_width;
			$image->height = $product->primary_image->thumb_height;
			$image->alt = sprintf(Store::_('Image of %s'), $product->title);
		}

		$this->ui->getWidget('image_edit')->title = Store::_('Replace Image');
		$this->ui->getWidget('image_delete')->visible = true;

	}
	// }}}
	// {{{ private function displayCategories()

	private function displayCategories($categories)
	{
		if (count($categories) == 1) {
			$category = $categories->getFirst();
			$navbar = new SwatNavBar();
			$navbar->addEntries($category->admin_navbar_entries);
			$navbar->display();

		// multiple categories, show in list
		} elseif (count($categories) > 1) {
			echo '<ul>';
				
			foreach ($categories as $category) {
				$navbar = new SwatNavBar();
				$navbar->addEntries($category->admin_navbar_entries);

				echo '<li>';
				$navbar->display();
				echo '</li>';
			}

			echo '</ul>';
		}
	}

	// }}}

	// build phase - items
	// {{{ protected function buildItems()

	protected function buildItems()
	{
		$view = $this->ui->getWidget('items_view');
		$form = $this->ui->getWidget('items_form');
		$view->addStyleSheet('packages/store/admin/styles/disabled-rows.css');
		$form->action = $this->getRelativeURL();
		$this->buildItemGroups();

		// show default status for new items
		$input_status =
			$view->getColumn('status')->getInputCell()->getPrototypeWidget();

		$input_status->content = Item::getStatusTitle(Item::STATUS_AVAILABLE);

		$this->ui->getWidget('status')->addOptionsByArray(Item::getStatuses());

		// setup the flydowns for enabled/disabled actions
		$regions = SwatDB::getOptionArray($this->app->db, 'Region', 'title',
			'id');
		$regions[0] = Store::_('All Regions');

		$this->ui->getWidget('enable_region')->addOptionsByArray(
			$regions);
		$this->ui->getWidget('disable_region')->addOptionsByArray(
			$regions);

		$view->getColumn('quantity_discounts')->getRendererByPosition()->db =
			$this->app->db;

		$this->appendCategoryToLinks();
	}

	// }}}
	// {{{ private function buildItemGroups()

	private function buildItemGroups()
	{
		$view = $this->ui->getWidget('items_view');
		$group_header = $view->getGroup('group');
		$groups = $this->queryItemGroups();
		$has_items = (count($groups) > 0);

		// if there is one row and the groupnum is 0 then there are no
		// item_groups with items in them for this product
		if (count($groups) == 0) {
			// there are no items
		} elseif (count($groups) == 1 && $groups->getFirst()->item_group == 0) {
			$num_groups = 0;
		} elseif ($groups->getFirst()->item_group == 0) {
			$num_groups = count($groups) - 1;
		} else {
			$num_groups = count($groups);
		}

		$group_info = array();
		foreach ($groups as $group)
			$group_info[$group->item_group] = $group->num_items;
		
		$group_header->group_info = $group_info;

		$order_link = $this->ui->getWidget('items_order');

		if ($has_items) {
			if ($num_groups == 0) {
				// order items link orders items
				// and don't show group headers
				$group_header->visible = false;
				// order link is insensitive if there is only 1 item
				$order_link->sensitive = ($groups->getFirst()->num_items > 1);
			} elseif ($num_groups == 1) {
				// order groups link is not sensitive.
				// order items through the group header
				$order_link->title = Store::_('Change Group Order');
				$order_link->sensitive = false;
			} else {
				// order items link orders item_groups
				$order_link->title = Store::_('Change Group Order');
				$order_link->link = 'ItemGroup/Order?product=%s';
			}
		} else {
			$order_link->sensitive = false;
		}
	}

	// }}}
	// {{{ private function queryItemGroups()

	private function queryItemGroups()
	{
		// get information about item groups used in this product
		$sql = 'select
					-- coalesce to 0 to match select query in getTableView()
					coalesce(item_group, 0) as item_group,
					count(id) as num_items
				from Item where
					product = %s and
					(item_group is null or
					item_group in (select id from ItemGroup where product = %s))
				group by item_group
				-- make sure the empty group is first
				order by item_group desc';
				
		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote($this->id, 'integer'));

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ private function appendCategoryToLinks()

	private function appendCategoryToLinks()
	{
		$toolbar = $this->ui->getWidget('items_toolbar');
		$view = $this->ui->getWidget('items_view');

		if ($this->category_id === null) {
			$toolbar->setToolLinkValues($this->id);
		} else {
			foreach ($toolbar->getToolLinks() as $tool_link)
				$tool_link->link.= '&category=%s';

			$toolbar->setToolLinkValues(array($this->id, $this->category_id));

			$link_suffix = sprintf('&category=%s', $this->category_id);

			foreach ($view->getColumns() as $column)
				foreach ($column->getRenderers() as $renderer)
					if ($renderer instanceof SwatLinkCellRenderer)
						$renderer->link.= $link_suffix;

			foreach ($view->getGroup('group')->getRenderers() as $renderer)
				if ($renderer instanceof SwatLinkCellRenderer)
					$renderer->link.= $link_suffix;
		}
	}

	// }}}
	// {{{ private function getItemsTableStore()

	private function getItemsTableStore($view)
	{
		/*
		 * This dynamic SQL is needed to make the table orderable by the price
		 * columns.
		 */
		$regions = $this->queryRegions();

		$regions_join_base =
			'left outer join ItemRegionBinding as ItemRegionBinding_%1$s
				on ItemRegionBinding_%1$s.item = Item.id
					and ItemRegionBinding_%1$s.region = %1$s';

		$regions_select_base = 'ItemRegionBinding_%1$s.price as price_%1$s';

		$regions_join = '';
		$regions_select = '';
		foreach ($regions as $region) {
			$regions_join.= sprintf($regions_join_base,
				$this->app->db->quote($region->id, 'integer')).' ';

			$regions_select.= sprintf($regions_select_base,
				$this->app->db->quote($region->id, 'integer')).', ';
		}

		$sql = 'select Item.*,
					-- regions select piece goes here
					%s
					-- put ungrouped items at the top
					coalesce(ItemGroup.displayorder, -1) as group_order
				from Item
					left outer join ItemGroup
						on ItemGroup.id = Item.item_group
					-- region join piece goes here
					%s
				where Item.product = %s
				order by group_order, Item.item_group, %s';

		$sql = sprintf($sql,
			$regions_select,
			$regions_join,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view,
				'Item.displayorder, Item.sku')); //TODO:, Item.part_count <- needs to go back to veseys

		$items = SwatDB::query($this->app->db, $sql, 'StoreItemWrapper');
		$store = new SwatTableStore();

		foreach ($items as $item) {
			$ds = new SwatDetailsStore($item);

			$ds->description = $item->getDescription();

			$ds->item_group_title = ($item->item_group === null) ?
				Store::_('[Ungrouped]') : $item->item_group->title;

			$ds->item_group_id = ($item->item_group === null) ?
				0 : $item->item_group->id;

			foreach ($this->queryRegions() as $region) {
				$price_field_name = sprintf('price_%s', $region->id);
				$enabled_field_name = sprintf('enabled_%s', $region->id);
				$ds->$price_field_name = null;
				$ds->$enabled_field_name = false;
			}

			$enabled = false;
			foreach ($item->region_bindings as $binding) {
				$price_field_name = 
					sprintf('price_%s', $binding->getInternalValue('region'));

				$enabled_field_name = 
					sprintf('enabled_%s', $binding->getInternalValue('region'));

				$ds->$price_field_name = $binding->price;
				$ds->$enabled_field_name = $binding->enabled;
				$enabled = $enabled || $binding->enabled;
			}
			$ds->enabled = $enabled;

			$store->addRow($ds);
		}

		return $store;
	}

	// }}}
	// {{{ private function queryRegions()

	private function queryRegions()
	{
		if ($this->regions === null) {
			$sql = 'select id, title from Region order by id';

			$this->regions =
				SwatDB::query($this->app->db, $sql, 'StoreRegionWrapper');
		}

		return $this->regions;
	}

	// }}}
	// {{{ private function appendPriceColumns()

	private function appendPriceColumns(SwatTableView $view, $regions)
	{
		foreach ($regions as $region) {
			$renderer = new StoreItemRegionPriceCellRenderer();
			$renderer->locale = $region->getFirstLocale()->id;

			$column = new SwatTableViewOrderableColumn('price_'.$region->id);
			$column->title = sprintf(Store::_('%s Price'), $region->title);
			$column->addRenderer($renderer);
			$column->addMappingToRenderer($renderer,
				'price_'.$region->id, 'value');

			$column->addMappingToRenderer($renderer,
				'enabled_'.$region->id, 'enabled');

			$money_entry = new SwatMoneyEntry('input_price_'.$region->id);
			$money_entry->locale = $region->getFirstLocale()->id;
			$money_entry->size = 4;

			$cell = new SwatInputcell();
			$cell->setWidget($money_entry);

			$column->setInputCell($cell);

			$view->appendColumn($column);

			// need to manually init here
			$column->init();
		}
	}

	// }}}

	// build phase - related products
	// {{{ private function buildRelatedProducts()

	private function buildRelatedProducts()
	{
		$toolbar = $this->ui->getWidget('related_products_toolbar');

		if ($this->category_id === null) {
			$toolbar->setToolLinkValues($this->id);
		} else {
			foreach ($toolbar->getToolLinks() as $tool_link)
				$tool_link->link.= '&category=%s';

			$toolbar->setToolLinkValues(array($this->id, $this->category_id));
		}
	}

	// }}}
	// {{{ private function getRelatedProductsTableStore()

	private function getRelatedProductsTableStore($view)
	{
		$sql = 'select id, title
			from Product
				inner join ProductRelatedProductBinding on id = related_product
					and source_product = %s
			order by title';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		if ($store->getRowCount() == 0) {
			$view->visible = false;
			$this->ui->getWidget('related_products_footer')->visible = false;
		}

		return $store;
	}

	// }}}
}

?>
