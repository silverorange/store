<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/dataobjects/StoreInvoice.php';

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

	protected $fields;
	protected $ui_xml = 'Store/admin/components/Invoice/itemedit.xml';

	// }}}
	// {{{ private properties

	/**
	 * @var StoreInvoice
	 */
	private $invoice;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		
		$this->fields = array('sku', 'description', 'integer:quantity',
			'float:price');

		$this->initInvoice();
	}

	// }}}
	// {{{ protected function initInvoice()

	protected function initInvoice() 
	{
		if ($this->id === null)
			$invoice_id = $this->app->initVar('invoice');
		else
			$invoice_id = SwatDB::queryOne($this->app->db,
				sprintf('select invoice from InvoiceItem where id = %s',
				$this->app->db->quote($this->id, 'integer')));

		$class_map = StoreClassMap::instance();
		$invoice_class = $class_map->resolveClass('StoreInvoice');
		$invoice = new $invoice_class();
		$invoice->setDatabase($this->app->db);

		if (!$invoice->load($invoice_id))
			throw new AdminNotFoundException(sprintf(
				Store::_('An invoice with an id of ‘%d’ does not exist.'),
				$this->id));

		$this->invoice = $invoice;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();

		if ($this->id === null) {
			$this->fields[] = 'integer:invoice';
			$values['invoice'] = $this->invoice->id;

			$this->id = SwatDB::insertRow($this->app->db, 'InvoiceItem',
				$this->fields, $values, 'id');
		} else {
			SwatDB::updateRow($this->app->db, 'InvoiceItem', $this->fields,
				$values, 'id', $this->id);
		}

		$message = new SwatMessage(sprintf(Store::_('“%s” has been saved.'),
			$values['sku']));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array('sku', 'description',
			'quantity', 'price'));
	}

	// }}}

	// build phase
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
		$row = SwatDB::queryRowFromTable($this->app->db, 'InvoiceItem',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('An invoice item with an id of ‘%d’ does not exist.'),
				$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}

?>
