<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatYUI.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/StoreItemStatusList.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';
require_once 'NateGoSearch/NateGoSearch.php';

/**
 * Edit page for Items
 *
 * @package   Store
 * @copyright 2005-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Item/edit.xml';
	protected $product;
	protected $item;

	// }}}
	// {{{ private properties

	/**
	 * Used to build the navbar.
	 *
	 * If the user navigated to this page from the Product Categories page then
	 *  then this variable will be set and will cause the navbar to display
	 *  differently.
	 *
	 * @var integer
	 */
	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->product     = SiteApplication::initVar('product');
		$this->category_id = SiteApplication::initVar('category');

		$this->initItem();

		if ($this->product === null && $this->item->id === null)
			throw new AdminNoAccessException(Store::_(
				'A product ID or an item ID must be passed in the URL.'));

		$status_radiolist = $this->ui->getWidget('status');
		foreach (StoreItemStatusList::statuses() as $status) {
			$status_radiolist->addOption(
				new SwatOption($status->id, $status->title));
		}

		$sale_discount_flydown = $this->ui->getWidget('sale_discount');
		$sale_discount_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'SaleDiscount', 'title', 'id', 'title'));

		$group_flydown = $this->ui->getWidget('minimum_quantity_group');
		$options = SwatDB::getOptionArray($this->app->db,
			'ItemMinimumQuantityGroup', 'title', 'id', 'title');

		$group_flydown->addOptionsByArray($options);
		$this->ui->getWidget('minimum_quantity_group_field')->visible =
			(count($options) > 0);

		$regions = SwatDB::getOptionArray($this->app->db, 'Region', 'title',
			'id', 'title');

		$price_replicator = $this->ui->getWidget('price_replicator');
		$price_replicator->replicators = $regions;

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('product', $this->product);
	}

	// }}}
	// {{{ protected function initItem()

	protected function initItem()
	{
		$class_name = SwatDBClassMap::get('StoreItem');
		$this->item = new $class_name();
		$this->item->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->item->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Store::_('Item with id "%s" not found.'),
						$this->id));
			else
				$this->product = $this->item->getInternalValue('product');
		}
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		/*
		 * Pre-process "enabled" checkboxes to set required flag on price
		 * entries.  Also set correct locale on the Price Entry.
		 */
		$sql = 'select id, title from Region order by Region.id';
		$regions = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreRegionWrapper'));

		$replicator = $this->ui->getWidget('price_replicator');

		foreach ($regions as $region) {
			$enabled_widget = $replicator->getWidget('enabled', $region->id);
			$enabled_widget->process();

			$price_widget = $replicator->getWidget('price', $region->id);
			$price_widget->required = $enabled_widget->value;
			$price_widget->locale = $region->getFirstLocale()->id;

			$original_price_widget =
				$replicator->getWidget('original_price', $region->id);

			$original_price_widget->locale = $region->getFirstLocale()->id;
		}

		parent::process();
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateItem();
		$this->item->save();
		$this->addToSearchQueue();

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $this->item->sku));

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}
	// {{{ protected function updateItem()

	protected function updateItem()
	{
		$values = $this->ui->getValues(array(
			'description',
			'sku',
			'status',
			'part_unit',
			'part_count',
			'singular_unit',
			'plural_unit',
			'sale_discount',
			'minimum_quantity_group',
			'minimum_quantity',
			'minimum_multiple',
			));

		$this->item->sku                    = trim($values['sku']);
		$this->item->description            = $values['description'];
		$this->item->part_unit              = $values['part_unit'];
		$this->item->part_count             = $values['part_count'];
		$this->item->singular_unit          = $values['singular_unit'];
		$this->item->plural_unit            = $values['plural_unit'];
		$this->item->sale_discount          = $values['sale_discount'];
		$this->item->minimum_quantity_group = $values['minimum_quantity_group'];
		$this->item->minimum_quantity       = $values['minimum_quantity'];
		$this->item->minimum_multiple       = $values['minimum_multiple'];
		$this->item->product                = $this->product;
		$this->item->setStatus(
			StoreItemStatusList::statuses()->getById($values['status']));

		$this->updateRegionBindings();
		$this->updateItemAliases();
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		$sql = sprintf('select catalog from Item
				inner join Product on Item.product = Product.id
				where Item.id %s %s',
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'));

		$catalog = SwatDB::queryOne($this->app->db, $sql);

		// validate main sku
		$sku = $this->ui->getWidget('sku');
		$valid =
			($this->item->sku !== null) ? array($this->item->sku) : array();

		if (!StoreItem::validateSku($this->app->db, $sku->value, $catalog,
			$this->product, $valid)) {
			$sku->addMessage(new SwatMessage(
				Store::_('%s must be unique amongst all catalogs unless '.
				'catalogs are clones of each other.')));
		}

		// validate alias skus
		$aliases = $this->ui->getWidget('aliases');
		if (count($aliases->values)) {
			$invalid_skus = array();
			$valid_skus = array();

			foreach ($aliases->values as $alias) {
				/*
				 * Checks the following:
				 * - alias is valid wrt catalogue
				 * - alias is not the same as current item sku
				 * - two of the same aliases are not entered at once
				 */
				if (!Item::validateSKU($this->app->db, $alias, $catalog,
					$this->product, $aliases->values) ||
					$alias == $sku->value || in_array($alias, $valid_skus))
						$invalid_skus[] = $alias;
				else
					$valid_skus[] = $alias;
			}

			if (count($invalid_skus) > 0) {
				$message = new SwatMessage(sprintf(Store::ngettext(
					'The following alias SKU already exists: %s',
					'The following alias SKUs already exist: %s',
					count($invalid_skus)), implode(', ', $invalid_skus)),
					SwatMessage::ERROR);

				$aliases->addMessage($message);
			}
		}

	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'product');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->product, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->product, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function updateRegionBindings()

	protected function updateRegionBindings()
	{
		// Due to SwatDBDataObject not being able to delete when there is no id
		// like the binding table below, this has to use manual sql to do its
		// delete, and can't use the nice removeAll() method.
		$delete_sql = 'delete from ItemRegionBinding where item = %s';
		$delete_sql = sprintf($delete_sql,
			$this->app->db->quote($this->item->id, 'integer'));

		SwatDB::exec($this->app->db, $delete_sql);

		$price_replicator = $this->ui->getWidget('price_replicator');
		$class_name = SwatDBClassMap::get('StoreItemRegionBinding');

		foreach ($price_replicator->replicators as $region => $title) {
			$price_field = $price_replicator->getWidget('price', $region);
			$enabled_field = $price_replicator->getWidget('enabled', $region);
			$original_price_field =
				$price_replicator->getWidget('original_price', $region);

			// only create new binding if price exists, otherwise there is no
			// use for the binding, and it can lead to bad data on the site
			if ($price_field->getState() !== null) {
				$region_binding = new $class_name();
				$region_binding->region  = $region;
				$region_binding->enabled = $enabled_field->value;
				$region_binding->price   = $price_field->value;
				$region_binding->original_price = $original_price_field->value;

				$this->item->region_bindings->add($region_binding);
			}
		}
	}

	// }}}
	// {{{ protected function updateItemAliases()

	protected function updateItemAliases()
	{
		$this->item->item_aliases->removeAll();

		$aliases = $this->ui->getWidget('aliases');
		if (count($aliases->values)) {
			$class_name = SwatDBClassMap::get('StoreItemAlias');

			foreach ($aliases->values as $alias) {
				$item_alias = new $class_name();
				$item_alias->sku = $alias;
				$this->item->item_aliases->add($item_alias);
			}
		}
	}

	// }}}

	// build phase
	// {{{ protected function display()

	protected function display()
	{
		parent::display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		// get rid of the items component, and the edit navbar entries
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
			'text:title', 'id', $this->product);

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $this->product);
		else
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product, $this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->title = $product_title;

		if ($this->id === null)
			$this->navbar->addEntry(new SwatNavBarEntry(Store::_('New Item')));
		else
			$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Edit Item')));
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->item));
		$this->ui->getWidget('status')->value = $this->item->getStatus()->id;

		if ($this->item->sale_discount !== null)
			$this->ui->getWidget('sale_discount')->value =
				$this->item->sale_discount->id;

		if ($this->item->minimum_quantity_group !== null)
			$this->ui->getWidget('minimum_quantity_group')->value =
				$this->item->minimum_quantity_group->id;

		$this->loadRegionBindings();
		$this->loadItemAliases();
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$price_replicator = $this->ui->getWidget('price_replicator');
		$replicator_ids = array_keys($price_replicator->replicators);
		$replicator_ids = implode(', ', $replicator_ids);
		$form_id = 'edit_form';
		return sprintf(
			"var item_edit_page = new StoreItemEditPage('%s', [%s]);",
			$form_id,
			$replicator_ids);
	}

	// }}}
	// {{{ protected function loadRegionBindings()

	protected function loadRegionBindings()
	{
		if ($this->id !== null) {
			$price_replicator = $this->ui->getWidget('price_replicator');

			// set all enabled to false on edits, as each region will set its
			// own enabled state in the next foreach loop.
			foreach ($price_replicator->replicators as $region => $title) {
				$price_replicator->getWidget('enabled', $region)->value = false;
			}

			foreach ($this->item->region_bindings as $binding) {
				$region_id = $binding->region->id;
				$price_replicator->getWidget('price', $region_id)->value =
					$binding->price;

				$price_replicator->getWidget('original_price', $region_id)->value =
					$binding->original_price;

				$price_replicator->getWidget('enabled', $region_id)->value =
					$binding->enabled;
			}
		}
	}

	// }}}
	// {{{ private function loadItemAliases()

	private function loadItemAliases()
	{
		$aliases = $this->ui->getWidget('aliases');
		foreach ($this->item->item_aliases as $alias)
			$aliases->values[] = $alias->sku;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/admin/javascript/store-item-edit-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/admin/styles/store-item-edit-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
