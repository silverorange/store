<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
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
		$this->initInvoice();
		$this->initInvoiceItem();
		$this->ui->getWidget('price')->locale = $this->invoice->locale->id;
	}

	// }}}
	// {{{ protected function initInvoiceItem()

	protected function initInvoiceItem()
	{
		$class = SwatDBClassMap::get('StoreInvoiceItem');
		$invoice_item = new $class();
		$invoice_item->setDatabase($this->app->db);

		if ($this->id === null) {
			$invoice_item->invoice = $this->invoice;
		} else {
			if (!$invoice_item->load($this->id)) {
				throw new AdminNotFoundException(sprintf(
					Store::_('Invoice item with id ‘%s’ not found.'),
					$this->id));
			}
		}

		$this->invoice_item = $invoice_item;
	}

	// }}}
	// {{{ protected function initInvoice()

	protected function initInvoice() 
	{
		if ($this->id === null) 
			$invoice_id = $this->app->initVar('invoice');
		else
			$invoice_id = SwatDB::queryOne($this->app->db, sprintf(
				'select invoice from InvoiceItem where id = %s',
				$this->app->db->quote($this->id, 'integer')));

		$class = SwatDBClassMap::get('StoreInvoice');
		$invoice = new $class();
		$invoice->setDatabase($this->app->db);
		if (!$invoice->load($invoice_id))
			throw new AdminNotFoundException(sprintf(
				Store::_('Invoice with id ‘%s’ not found.'),
				$this->id));

		$this->invoice = $invoice;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->invoice_item->sku = $this->ui->getWidget('sku')->value;
		$this->invoice_item->quantity = $this->ui->getWidget('quantity')->value;
		$this->invoice_item->price = $this->ui->getWidget('price')->value;
		$this->invoice_item->description =
			$this->ui->getWidget('description')->value;

		$this->invoice_item->save();

		$message = new SwatMessage(sprintf(
			Store::_('“%s” has been saved.'),
			$this->invoice_item->getDetailedDescription()));

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
			$this->invoice->id);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('invoice', $this->invoice->id);
	}

	// }}}
	// {{{ protected buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->invoice->account->fullname,
			sprintf('Account/Details?id=%s', $this->invoice->account->id)));

		$this->navbar->addEntry(new SwatNavBarEntry(
			sprintf(Store::_('Invoice %s'), $this->invoice->id),
			sprintf('Invoice/Details?id=%s', $this->invoice->id)));

		$title = ($this->id === null) ?
			Store::_('Add Invoice Item') : Store::_('Edit Invoice Item');

		$this->navbar->addEntry(new SwatNavBarEntry($title));
	}
	
	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->invoice_item));
	}

	// }}}
}

?>
