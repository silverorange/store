<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';
require_once 'Store/dataobjects/StoreInvoiceItem.php';

/**
 * Delete confirmation page for invoice items
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceItemDelete extends AdminDBDelete
{
	// init phase
	// {{{ protected function getInvoice()

	protected function getInvoice()
	{
		$class_map = StoreClassMap::instance();
		$invoice_class = $class_map->resolveClass('StoreInvoiceItem');
		$invoice_item = new $invoice_class();
		$invoice_item->setDatabase($this->app->db);

		if (!$invoice_item->load($this->getFirstItem()))
			throw new AdminNotFoundException(sprintf(
				Store::_('An invoice item with an id of ‘%d’ does not exist.'),
				$this->id));

		return $invoice_item->invoice;
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from InvoiceItem where id in (%s)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(
			Store::ngettext('One invoice item has been deleted.',
			'%d invoice items have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildNavBar();

		$form = $this->ui->getWidget('confirmation_form');

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(Store::_('invoice item'), Store::_('invoice items'));

		$sql = sprintf('select id, sku, description, price
			from InvoiceItem where id in (%s)',
			$item_list);

		$invoice = $this->getInvoice();
		$entries = array();
		$rows = SwatDB::query($this->app->db, $sql);

		foreach ($rows as $row) {
			$entry = new AdminDependencyEntry();
			$entry->id = $row->id;
			$entry->status_level = AdminDependency::DELETE;

			$title = array();

			if ($row->sku !== null)
				$title[] = $row->sku;

			if ($row->description !== null)
				$title[] = $row->description;

			$title[] = SwatString::moneyFormat($row->price, $invoice->locale->id);

			$entry->title = implode(' - ', $title);

			$entries[] = $entry;
		}

		$dep->entries = &$entries;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar() 
	{
		$invoice = $this->getInvoice();

		$fullname = $invoice->account->fullname;

		$last_entry = $this->navbar->popEntry();

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry($fullname,
			'Account/Details?id='.$invoice->account->id));

		$this->navbar->addEntry(new SwatNavBarEntry(
			sprintf('Invoice %s', $invoice->id),
			sprintf('Invoice/Details?id=%s', $invoice->id)));

		$this->navbar->addEntry($last_entry);
	}

	// }}}
}

?>
