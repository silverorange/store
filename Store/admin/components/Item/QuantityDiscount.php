<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMoneyEntry.php';
require_once 'Swat/SwatBooleanCellRenderer.php';
require_once 'Swat/SwatCheckbox.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';
require_once 
	'Store/admin/components/Item/include/StoreItemQuantityDiscountActions.php';

require_once 
	'Store/admin/components/Item/include/StoreItemQuantityCellRenderer.php';

require_once 'Store/admin/components/Item/include/'.
	'StoreItemQuantityDiscountTableView.php';

require_once 'Store/admin/components/Product/include/'.
	'StoreItemRegionPriceCellRenderer.php';

/**
 * Quantity discounts tool
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemQuantityDiscount extends AdminIndex
{
	// {{{ private properties

	private $id;
	private $category_id;
	private $regions = null;
	private $item = null;
	private $initial_input_row_state = array();

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initial_input_row_state = $this->getInputRowState();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/quantity-discount.xml');

		$this->id = SiteApplication::initVar('id');

		if ($this->id === null)
			throw new AdminNoAccessException(Store::_('An item ID is required '.
				'for the quantity discounts page.'));

		$this->category_id = SiteApplication::initVar('category');

		$regions = $this->queryRegions();
		$view = $this->ui->getWidget('index_view');

		//TODO: add this back to veseys
		$item_row = $this->getItemRow();
		$quantity =
			$view->getColumn('quantity')->getInputCell()->getPrototypeWidget();

		$quantity->minimum_value = $item_row->quantity;
		//$quantity->minimum_value = 1;

		// add dynamic columns to view
		$this->appendPriceColumns($view, $regions);
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
	// {{{ private function getItemRow()

	private function getItemRow()
	{
		if ($this->item === null) {
			$regions = $this->queryRegions();

			$regions_join_base = 
				'left outer join ItemRegionBinding as ItemRegionBinding_%s
					on ItemRegionBinding_%s.item = Item.id
						and ItemRegionBinding_%s.region = %s';
								
			$regions_select_base = 'ItemRegionBinding_%s.price as price_%s';

			$regions_join = '';
			$regions_select = '';
			foreach ($regions as $region) {
				$regions_join.= sprintf($regions_join_base,
					$region->id,
					$region->id,
					$region->id,
					$this->app->db->quote($region->id, 'integer')).' ';

				$regions_select.= sprintf($regions_select_base,
					$region->id,
					$region->id).', ';
			}

			$sql = 'select sku, product, description,
						-- minimum_quantity as quantity, TODO: this needs to go back in for veseys
						-- regions select piece goes here
						%s
						-- unit TODO: this needs to go back in for veseys
						1 as quantity
					from Item
						-- regions join piece goes here
						%s
					where Item.id = %s';

			$this->item = SwatDB::queryRow($this->app->db, sprintf($sql,
				$regions_select,
				$regions_join,
				$this->app->db->quote($this->id, 'integer')));
		}

		return $this->item;
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
			$column->addMappingToRenderer($renderer, 'price_'.$region->id,
				'value');

			$money_entry = new SwatMoneyEntry('input_price_'.$region->id);
			$money_entry->locale = $region->getFirstLocale()->id;
			$money_entry->size = 5;

			$cell = new SwatInputCell();
			$cell->setWidget($money_entry);

			$column->setInputCell($cell);

			$view->appendColumn($column);

			// need to manually init here
			$column->init();
		}
	}

	// }}}
	// {{{ private function getInputRowState()

	private function getInputRowState()
	{
		$states = array();

		$view = $this->ui->getWidget('index_view');
		$input_row = $view->getFirstRowByClass('SwatTableViewInputRow');
		$replicators = $input_row->getReplicators();

		$regions = $this->queryRegions();

		foreach ($replicators as $replicator_id) {
			$row_state = array();

			$quantity = $input_row->getWidget('quantity', $replicator_id);
			$row_state['quantity'] = $quantity->getState();

			foreach ($regions as $region) {
				$price = $input_row->getWidget('price_'.$region->id,
					$replicator_id);

				$row_state['price_'.$region->id] = $price->getState();
			}

			$states[$replicator_id] = md5(serialize($row_state));
		}

		return $states;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		if ($this->ui->getWidget('index_form')->isProcessed()) {

			// add new quantity discounts
			if ($this->ui->getWidget('index_actions')->selected !== null &&
				$this->ui->getWidget('index_actions')->selected->id == 'add') {

				$this->addNewQuantityDiscounts();
			} else {
				// Don't show validation messages on the input row if we did
				// not chose the 'add quantity discounts' action.
				$view = $this->ui->getWidget('index_view');
				$input_row = $view->getFirstRowByClass('SwatTableViewInputRow');
				$input_row->show_row_messages = false;

				// If we're not adding rows and there are no checked items
				// then we always want to relocate, even if delete is selected.
				if (count($view->checked_items) == 0)
					$this->relocate();
			}
		}
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->checked_items);

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Item/QuantityDiscountDelete');
			$this->app->getPage()->setItems($view->checked_items);
			$this->app->getPage()->setRelocateUrl($this->getProductDetailsUrl());
			break;

		default:
			$this->relocate();
		}
	}

	// }}}
	// {{{ private function relocate()

	/**
	 * Relocates back to product details if the done button was clicked
	 */
	private function relocate()
	{
		$done_button =
			$this->ui->getWidget('index_actions')->getDoneButton();

		if ($done_button->hasBeenClicked())
			$this->app->relocate($this->getProductDetailsUrl());
	}

	// }}}
	// {{{ private function getProductDetailsUrl()

	private function getProductDetailsUrl()
	{
		$item_row = $this->getItemRow();
		if ($this->category_id === null)
			$url = 'Product/Details?id='.$item_row->product;
		else
			$url = 'Product/Details?id='.$item_row->product.'&category='.
				$this->category_id;

		return $url;
	}

	// }}}
	// {{{ private function addNewQuantityDiscounts()

	private function addNewQuantityDiscounts()
	{
		$view = $this->ui->getWidget('index_view');
		$input_row = $view->getFirstRowByClass('SwatTableViewInputRow');

		$fields = array('integer:item', 'integer:quantity');

		$region_fields = array('integer:quantity_discount', 'integer:region',
			'decimal:price');

		$this->removeEmptyRows($input_row);

		$new_discounts = array();
		$has_invalid_row = false;
		$regions = $this->queryRegions();
		$replicators = $input_row->getReplicators();

		foreach ($replicators as $replicator_id) {
			if ($this->validateRow($input_row, $replicator_id)) {

				$quantity = $input_row->getWidget('quantity', 
					$replicator_id)->getState();

				$values = array('item' => $this->id, 'quantity' => $quantity);

				$discount_id = SwatDB::insertRow($this->app->db,
					'QuantityDiscount', $fields, $values, 'id');

				foreach ($regions as $region) {
					$price = $input_row->getWidget('price_'.$region->id,
						$replicator_id);

					if ($price->getState() !== null) {
						$region_values = array(
							'quantity_discount' => $discount_id,
							'region' => $region->id,
							'price' => $price->getState());

						SwatDB::insertRow($this->app->db,
							'QuantityDiscountRegionBinding', $region_fields,
							$region_values);
					}
				}

				// remove the row after we entered it
				$input_row->removeReplicatedRow($replicator_id);

				$new_discounts[] = SwatString::minimizeEntities($quantity);
			} else {
				$has_invalid_row = true;
			}
		}

		if (count($new_discounts) == 1) {
			$msg = new SwatMessage(sprintf(Store::_('“%s” has been added.'),
					$new_discounts[0]));

			$this->app->messages->add($msg);
		} elseif (count($new_discounts) > 1) {
			$discount_list = '<ul><li>'.implode('</li><li>',
				$new_discounts).'</li></ul>';

			$msg = new SwatMessage(
				Store::_('The following quantity discounts have been added:'));

			$msg->secondary_content = $discount_list;
			$msg->content_type = 'text/xml';
			$this->app->messages->add($msg);
		}

		if ($has_invalid_row) {
			$msg = new SwatMessage(Store::_('There was a problem adding the '.
				'quantity discount(s). Please review the highlighted fields '.
				'for errors and try again.'), SwatMessage::ERROR);

			$this->app->messages->add($msg);
		} else {
			$this->relocate();
		}
	}

	// }}}
	// {{{ private function removeEmptyRows()

	private function removeEmptyRows($input_row)
	{
		$replicators = $input_row->getReplicators();
		$row_state = $this->getInputRowState();
		foreach ($replicators as $id)
			if ($row_state[$id] == $this->initial_input_row_state[$id])
				$input_row->removeReplicatedRow($id);
	}

	// }}}
	// {{{ private function validateRow()

	private function validateRow($input_row, $replicator_id)
	{
		$valid = !$input_row->rowHasMessage($replicator_id);

		// validate quantity (must be unique per item)
		$quantity = $input_row->getWidget('quantity', $replicator_id);
		$sql = sprintf('select count(id) from QuantityDiscount
				where item = %s and quantity %s %s',
			$this->app->db->quote($this->id),
			SwatDB::equalityOperator($quantity->getState()),
			$this->app->db->quote($quantity->getState()));
		
		$unique = (SwatDB::queryOne($this->app->db, $sql) == 0);
		if (!$unique) {
			$quantity->addMessage(new SwatMessage(Store::_('%s must be unique '.
				'for each item. If you want to update the prices for a '.
				'quantity discount, first delete the old quantity discount.')));

			$valid = false;
		}

		// validate prices (must enter at least one)
		$regions = $this->queryRegions();
		$has_price = false;
		foreach ($regions as $region) {
			$price = $input_row->getWidget('price_'.$region->id,
				$replicator_id);

			if ($price->getState() !== null) {
				$has_price = true;
				break;
			}
		}
		if (!$has_price) {
			$price->addMessage(new SwatMessage(
				Store::_('At least one price is required')));

			$valid = false;
		}

		return $valid;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_row = $this->getItemRow();
		$this->ui->getWidget('index_view')->setItemRow($item_row);

		if ($item_row->description === null)
			$this->ui->getWidget('index_frame')->subtitle = sprintf(
				Store::_('for SKU %s'), $item_row->sku);

		else
			$this->ui->getWidget('index_frame')->subtitle = sprintf(
				Store::_('for %s (%s)'), $item_row->description,
				$item_row->sku);

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$regions = $this->queryRegions();

		$regions_join_base = 
			'left outer join QuantityDiscountRegionBinding as
					QuantityDiscountRegionBinding_%s
				on QuantityDiscountRegionBinding_%s.quantity_discount =
					QuantityDiscount.id
					and QuantityDiscountRegionBinding_%s.region = %s';

		$regions_select_base =
			'QuantityDiscountRegionBinding_%s.price as price_%s';

		$regions_join = '';
		$regions_select = '';
		foreach ($regions as $region) {
			$regions_join.= sprintf($regions_join_base,
				$region->id,
				$region->id,
				$region->id,
				$this->app->db->quote($region->id, 'integer')).' ';

			$regions_select.= sprintf($regions_select_base,
				$region->id,
				$region->id).', ';
		}

		$sql = 'select id,
					-- regions select piece goes here
					%s
					quantity
				from QuantityDiscount
					-- regions join piece goes here
					%s
				where item = %s order by %s';

		$sql = sprintf($sql,
			$regions_select,
			$regions_join,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view, 'quantity'));

		return SwatDB::query($this->app->db, $sql, 'AdminTableStore');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar() 
	{
		$item_row = $this->getItemRow();
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
			'text:title', 'id', $item_row->product);

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $item_row->product);
		else
			$link = sprintf('Product/Details?id=%s&category=%s',
				$item_row->product, $this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->title = $product_title;

		$this->navbar->addEntry(new SwatNavBarEntry(sprintf(
			Store::_('Quantity Discounts for SKU %s'), $item_row->sku)));
	}

	// }}}
	// {{{ protected function buildForms()

	protected function buildForms()
	{
		parent::buildForms();

		// always show actions even when there are no entries in the table
		$this->ui->getWidget('index_actions')->visible = true;
	}

	// }}}
}

?>
