<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBOrder.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/dataobjects/StoreInvoice.php';
require_once 'Store/dataobjects/StoreInvoiceItemWrapper.php';

/**
 * Order page for invoice items
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceItemOrder extends AdminDBOrder
{
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

		$id = SiteApplication::initVar('id');

		$class_map = StoreClassMap::instance();
		$class_name = $class_map->resolveClass('StoreInvoice');
		$this->invoice = new $class_name();
		$this->invoice->setDatabase($this->app->db);
		if (!$this->invoice->load($id)) {
			throw new AdminNotFoundException(sprintf(
				Store::_('Invoice with id of ‘%s’ does not exist.'), $id));
		}

		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('id', $this->invoice->id);
	}

	// }}}

	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'InvoiceItem',
			'integer:displayorder', $index, 'integer:id', array($id));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Store::_('Order Invoice Items');
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{ 
		$where_clause = sprintf('where invoice %s %s',
			SwatDB::equalityOperator($this->invoice->id),
			$this->app->db->quote($this->invoice->id, 'integer'));

		$order_widget = $this->ui->getWidget('order');
		foreach ($this->invoice->items as $item) {
			$title = $item->getDetailedDescription();

			// indent items that don't have SKUs
			if ($item->sku === null)
				$title = '&nbsp;&nbsp;&nbsp;&nbsp;'.$title;

			$option = new SwatOption($item->id, $title);
			$order_widget->addOption($option);
		}

		$sql = sprintf('select sum(displayorder) from InvoiceItem %s',
			$where_clause);

		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$fullname = $this->invoice->account->fullname;
		$title = sprintf(Store::_('Invoice %s'),
			$this->invoice->id);

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry($fullname,
			'Account/Details?id='.$this->invoice->account->id));

		$this->navbar->addEntry(new SwatNavBarEntry($title,
			'Invoice/Details?id='.$this->invoice->id));

		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Order Invoice Items')));
	}

	// }}}
}

?>
