<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatYUI.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/StoreItemStatusList.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';

/**
 * Edit page for Items
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Item/edit.xml';
	protected $fields;
	protected $product_id;

	// }}}
	// {{{ private properties

	private $category_id;
	private $item_sku;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->ui->loadFromXML($this->ui_xml);
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/admin/javascript/store-item-edit-page.js',
			Store::PACKAGE_ID));

		// TODO: move this style to Store
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'styles/item-edit-page.css', Store::PACKAGE_ID));

		$this->fields = array('description', 'sku', 'integer:status');

		$this->product_id = SiteApplication::initVar('product');
		$this->category_id = SiteApplication::initVar('category');
		$this->item_sku = SiteApplication::initVar('item_sku');

		if ($this->product_id === null && $this->id === null) {
			throw new AdminNoAccessException(Store::_(
				'A product ID or an item ID must be passed in the URL.'));
		} elseif ($this->product_id === null) {
			$this->product_id = SwatDB::queryOne($this->app->db,
				sprintf('select product from Item where id = %s',
				$this->app->db->quote($this->id, 'integer')));
		}

		$status_radiolist = $this->ui->getWidget('status');
		foreach (StoreItemStatusList::statuses() as $status) {
			$status_radiolist->addOption(
				new SwatOption($status->id, $status->title));
		}

		$regions = SwatDB::getOptionArray($this->app->db, 'Region', 'title',
			'id', 'title');

		$price_replicator = $this->ui->getWidget('price_replicator');
		$price_replicator->replicators = $regions;
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
		$regions = SwatDB::query($this->app->db, $sql, 'StoreRegionWrapper');

		$replicator = $this->ui->getWidget('price_replicator');

		foreach ($regions as $region) {
			$enabled_widget = $replicator->getWidget('enabled', $region->id);
			$enabled_widget->process();
			
			$price_widget = $replicator->getWidget('price', $region->id);
			$price_widget->required = $enabled_widget->value;
			$price_widget->locale = $region->getFirstLocale()->id;
		}

		parent::process();
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();

		if ($this->id === null) {
			$this->fields[] = 'product';
			$values['product'] =
				$this->ui->getWidget('edit_form')->getHiddenField('product');

			$this->id = SwatDB::insertRow($this->app->db, 'Item',
				$this->fields, $values, 'id');
		} else {
			SwatDB::updateRow($this->app->db, 'Item', $this->fields, $values,
				'id', $this->id);
		}

		$this->addToSearchQueue();
		$this->saveItemRegionFields();

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['sku']));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array('description', 'sku', 'status'));
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
		$valid = ($this->item_sku !== null) ? array($this->item_sku) : array();
		if (!StoreItem::validateSku($this->app->db, $sku->value, $catalog,
			$this->product_id, $valid)) {
			$sku->addMessage(new SwatMessage(
				Store::_('%s must be unique amongst all catalogs unless '.
				'catalogs are clones of each other.')));
		}
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->product_id, 'integer'),
			$this->app->db->quote(2, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->product_id, 'integer'),
			$this->app->db->quote(2, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ private function saveItemRegionFields()

	private function saveItemRegionFields()
	{
		/*
		 * NOTE: This stuff is automatically wrapped in a transaction in
		 *       AdminDBEdit::saveData()
		 *
		 * Once upon a time, we checked to see if there was an entry in the
		 * ItemRegionBinding table per region to see if the item was enabled in
		 * the region, but realized this meant we dropped any pricing data
		 * upon disabling, which sucks.  So now we use the enabled bit on the
		 * row, and hence we always insert the row, regardless of whether price
		 * is null
		 */

		$delete_sql = 'delete from ItemRegionBinding where item = %s';
		$delete_sql = sprintf($delete_sql,
			$this->app->db->quote($this->id, 'integer'));

		SwatDB::query($this->app->db, $delete_sql);

		$insert_sql = 'insert into ItemRegionBinding 
			(item, region, price, enabled)
			values (%s, %%s, %%s, %%s)';

		$insert_sql = sprintf($insert_sql,
			$this->app->db->quote($this->id, 'integer'));

		$price_replicator = $this->ui->getWidget('price_replicator');

		foreach ($price_replicator->replicators as $region => $title) {
			$price_field = $price_replicator->getWidget('price', $region);
			$enabled_field = $price_replicator->getWidget('enabled', $region);

			$sql = sprintf($insert_sql,
				$this->app->db->quote($region, 'integer'),
				$this->app->db->quote($price_field->value, 'decimal'),
				$this->app->db->quote($enabled_field->value, 'boolean'));

			SwatDB::query($this->app->db, $sql);
		}
	}

	// }}}

	// build phase
	// {{{ protected function display()

	protected function display()
	{
		parent::display();
		$this->displayJavaScript();
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('product', $this->product_id);
		$form->addHiddenField('category', $this->category_id);
		$form->addHiddenField('item_sku', $this->item_sku);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->popEntry();

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
		$row = SwatDB::queryRowFromTable($this->app->db, 'Item',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('Item with id ‘%s’ not found.'), $this->id));

		$row = SwatDB::queryRowFromTable($this->app->db, 'Item', $this->fields,
			'id', $this->id);

		$this->item_sku = $row->sku;
		$this->ui->setValues(get_object_vars($row));
		$this->loadReplicators();
	}

	// }}}
	// {{{ protected function displayJavaScript()

	protected function displayJavaScript()
	{
		$price_replicator = $this->ui->getWidget('price_replicator');
		$replicator_ids = array_keys($price_replicator->replicators);
		$replicator_ids = implode(', ', $replicator_ids);
		$form_id = 'edit_form';

		echo '<script type="text/javascript">', "\n//<![CDATA[\n";
		printf("var item_edit_page = new StoreItemEditPage('%s', [%s]);",
			$form_id,
			$replicator_ids);

		echo "\n//]]>\n</script>";
	}

	// }}}
	// {{{ private function loadReplicators()

	private function loadReplicators()
	{
		$price_replicator = $this->ui->getWidget('price_replicator');

		$sql = sprintf('select Region.id as region, price, enabled
			from Region
			left outer join ItemRegionBinding on
				ItemRegionBinding.region = Region.id
				and item = %s'.;
			$this->app->db->quote($this->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);
		foreach ($rs as $row) {
			$price_replicator->getWidget('price', $row->region)->value =
				$row->price;

			$price_replicator->getWidget('enabled', $row->region)->value =
				$row->enabled;
		}
	}

	// }}}
}

?>
