<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/dataobjects/StoreInvoice.php';
require_once 'Store/dataobjects/StoreInvoiceItem.php';

/**
 * Edit page for invoice items
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceItemEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Invoice/itemedit.xml';

	/**
	 * @var StoreInvoiceItem
	 */
	protected $invoice_item;

	/**
	 * @var StoreInvoice
	 */
	protected $invoice;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ protected function getInvoiceItem()

	protected function getInvoiceItem()
	{
		if ($this->invoice_item === null) {
			$class_map = StoreClassMap::instance();
			$class_name = $class_map->resolveClass('StoreInvoiceItem');
			$this->invoice_item = new $class_name();
			$this->invoice_item->setDatabase($this->app->db);

			if ($this->id === null) {
				$this->invoice_item->invoice = $this->getInvoice();
			} else {
				if (!$this->invoice_item->load($this->id)) {
					throw new AdminNotFoundException(sprintf(
						Store::_('Invoice item with id ‘%s’ not found.'),
						$this->id));
				}
			}
		}

		return $this->invoice_item;
	}

	// }}}
	// {{{ protected function getInvoice()

	protected function getInvoice() 
	{
		if ($this->invoice === null) {
			if ($this->id === null) 
				$invoice_id = $this->app->initVar('invoice');
			else
				$invoice_id = SwatDB::queryOne($this->app->db, sprintf(
					'select invoice from InvoiceItem where id = %s',
					$this->app->db->quote($this->id, 'integer')));

			$class_map = StoreClassMap::instance();
			$invoice_class = $class_map->resolveClass('StoreInvoice');
			$this->invoice = new $invoice_class();
			$this->invoice->setDatabase($this->app->db);
			if (!$this->invoice->load($invoice_id))
				throw new AdminNotFoundException(sprintf(
					Store::_('Invoice with id ‘%d’ not found.'),
					$this->id));
		}

		return $this->invoice;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$invoice_item = $this->getInvoiceItem();
		$invoice_item->sku = $this->ui->getWidget('sku')->value;
		$invoice_item->description = $this->ui->getWidget('description')->value;
		$invoice_item->quantity = $this->ui->getWidget('quantity')->value;
		$invoice_item->price = $this->ui->getWidget('price')->value;
		$invoice_item->save();

		$message = new SwatMessage(sprintf(
			Store::_('“%s” has been saved.'),
			$invoice_item->getDetailedDescription()));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$frame = $this->ui->getWidget('edit_frame');
		$frame->subtitle = sprintf(Store::_('Invoice %s'),
			$this->getInvoice()->id);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('invoice', $this->getInvoice()->id);
	}

	// }}}
	// {{{ protected buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$invoice = $this->getInvoice();

		$this->navbar->popEntry();

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry(
			$invoice->account->fullname,
			sprintf('Account/Details?id=%s', $invoice->account->id)));

		$this->navbar->addEntry(new SwatNavBarEntry(
			sprintf(Store::_('Invoice %s'), $invoice->id),
			sprintf('Invoice/Details?id=%s', $invoice->id)));

		$title = ($this->id === null) ?
			Store::_('Add Invoice Item') : Store::_('Edit Invoice Item');

		$this->navbar->addEntry(new SwatNavBarEntry($title));
	}
	
	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$invoice_item = $this->getInvoiceItem();

		$this->ui->getWidget('sku')->value = $invoice_item->sku;
		$this->ui->getWidget('description')->value = $invoice_item->description;
		$this->ui->getWidget('quantity')->value = $invoice_item->quantity;
		$this->ui->getWidget('price')->value = $invoice_item->price;
	}

	// }}}
}

?>
