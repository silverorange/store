<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNavBar.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/StoreTotalRow.php';
require_once 'Store/dataobjects/StoreInvoice.php';
require_once 'Store/dataobjects/StoreOrderAddress.php';
require_once 'Store/admin/components/Invoice/include/StoreInvoiceTotalRow.php';

/**
 * Details page for Invoices
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceDetails extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Invoice/details.xml';
	protected $id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->id = SiteApplication::initVar('id');
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		// add new items
		if ($this->ui->getWidget('index_actions')->selected !== null &&
			$this->ui->getWidget('index_actions')->selected->id == 'add') {

			$this->addNewItems();
		}
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($view->id) {
		case 'items_view':
			$this->processItemsActions($view, $actions);
			break;
		}
	}

	// }}}
	// {{{ protected function processItemsActions()

	protected function processItemsActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Invoice/ItemDelete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}
	// {{{ protected function validateItemRows()

	protected function validateItemRows($input_row)
	{
		$validate = true;
		$replicators = $input_row->getReplicators();

		foreach ($replicators as $replicator_id) {
			//TODO: validation goes here
		}

		return $validate;
	}

	// }}}
	// {{{ protected function addNewItemExtras()

	protected function addNewItemExtras($item_id)
	{
		/**
		 * this is a placeholder function, for the occasional case where a site
		 * would require that we insert rows into other tables on item creation
		 */
	}

	// }}}
	// {{{ protected function addNewItems()

	protected function addNewItems()
	{
		$sql = sprintf('select account from Invoice where id = %s',
			$this->app->db->quote($this->id, 'integer'));

		$account = SwatDB::queryOne($this->app->db, $sql);

		$view = $this->ui->getWidget('items_view');
		$input_row = $view->getRow('input_row');

		$fields = array(
			'integer:invoice',
			'text:sku',
			'text:description',
			'integer:quantity',
			'float:price',
			'integer:displayorder',
		);

		// get highest displayorder so newest items are always added at the
		// bottom of the list
		$displayorder_sql = sprintf('select max(displayorder) from InvoiceItem
			where invoice = %s',
			$this->app->db->quote($this->id, 'integer'));

		$displayorder = SwatDB::queryOne($this->app->db, $displayorder_sql);

		if ($this->validateItemRows($input_row)) {
			$new_skus = 0;
			$replicators = $input_row->getReplicators();
			foreach ($replicators as $replicator_id) {
				if (!$input_row->rowHasMessage($replicator_id)) {
					$sku = $input_row->getWidget(
						'sku', $replicator_id)->value;

					$description = $input_row->getWidget(
						'description', $replicator_id)->value;

					$price = $input_row->getWidget(
						'price', $replicator_id)->value;

					$quantity = $input_row->getWidget(
						'quantity', $replicator_id)->value;

					// Create new item
					$values = array(
						'invoice'      => $this->id,
						'sku'          => $sku,
						'description'  => $description,
						'price'        => $price,
						'quantity'     => $quantity,
						'displayorder' => $displayorder,
					);

					$item_id = SwatDB::insertRow($this->app->db, 'InvoiceItem',
						$fields, $values, 'id');

					$this->addNewItemExtras($item_id);

					// remove the row after we entered it
					$input_row->removeReplicatedRow($replicator_id);

					$new_skus++;
				}
			}

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One item has been added.', '%s items have been added.',
				$new_skus), SwatString::numberFormat($new_skus)));

			$this->app->messages->add($message);
		} else {
			$message = new SwatMessage(Store::_('There was a problem adding '.
				'the item(s). Please check the highlighted fields below.'),
				SwatMessage::ERROR);

			$this->app->messages->add($message);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildInvoice();
		$this->buildItems();
	}

	// }}}
	// {{{ protected function buildForms()

	protected function buildForms()
	{
		parent::buildForms();

		// always show add new item action regardless of entries in item table
		// but also keep all other actions hidden
		if ($this->ui->getWidget('items_view')->model->getRowCount() == 0) {
			$index_actions = $this->ui->getWidget('index_actions');
			$index_actions->visible = true;
			foreach ($index_actions->getActionItems() as $id => $widget)
				if ($widget->id !== 'add') $widget->visible = false;
		}
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		switch ($view->id) {
			case 'items_view':
				return $this->getItemsTableStore($view);
		}
	}

	// }}}

	// build phase - invoice details
	// {{{ protected function getInvoiceDetailsStore()

	protected function getInvoiceDetailsStore($invoice)
	{
		$ds = new SwatDetailsStore($invoice);

		// format the bodytext
		$ds->comments = SwatString::condense(SwatString::toXHTML(
			$invoice->comments));

		return $ds;
	}

	// }}}
	// {{{ private function buildInvoice()

	private function buildInvoice()
	{
		$invoice = $this->loadInvoice();

		$ds = $this->getInvoiceDetailsStore($invoice);
		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;

		$title =  sprintf(Store::_('Invoice %s'), $this->id);

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = $title;
		$this->title = $title;

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues($this->id);
		$toolbar = $this->ui->getWidget('items_toolbar');
		$toolbar->setToolLinkValues($this->id);

		$locale_id = $invoice->locale->id;
		$view = $this->ui->getWidget('items_view');

		$view->getRow('subtotal')->locale = $locale_id;
		$view->getRow('subtotal')->value = $invoice->getSubtotal();
		$view->getRow('shipping')->locale = $locale_id;
		$view->getRow('shipping')->value = $invoice->getShippingTotal();
		$view->getRow('tax')->locale = $locale_id;
		$view->getRow('tax')->value = $invoice->getTaxTotal();
		$view->getRow('total')->locale = $locale_id;
		$view->getRow('total')->value = $invoice->getTotal();

		$view->getColumn('price')->getFirstRenderer()->locale = $locale_id;

		$this->buildNavBar($invoice);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar($invoice)
	{
		$fullname = $invoice->account->fullname;

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry($fullname,
			'Account/Details?id='.$invoice->account->id));

		$this->navbar->addEntry(new SwatNavBarEntry($this->title));
	}

	// }}}
	// {{{ private function loadInvoice()

	private function loadInvoice()
	{
		$class_map = StoreClassMap::instance();
		$invoice_class = $class_map->resolveClass('StoreInvoice');
		$invoice = new $invoice_class();
		$invoice->setDatabase($this->app->db);

		if (!$invoice->load($this->id))
			throw new AdminNotFoundException(sprintf(
				Store::_('An invoice with an id of ‘%d’ does not exist.'),
				$this->id));

		return $invoice;
	}

	// }}}

	// build phase - items
	// {{{ protected function buildItems()

	protected function buildItems()
	{
		$view = $this->ui->getWidget('items_view');
		$toolbar = $this->ui->getWidget('items_toolbar');
		$form = $this->ui->getWidget('items_form');
		$view->addStyleSheet('packages/store/admin/styles/disabled-rows.css');
		$form->action = $this->getRelativeURL();
	}

	// }}}
	// {{{ protected function getItemsTableStore()

	protected function getItemsTableStore($view)
	{
		$sql = sprintf('select *, price * quantity as total from InvoiceItem
			where invoice = %s order by %s';
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view, 'displayorder, id'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');
		return $store;
	}

	// }}}
}

?>
