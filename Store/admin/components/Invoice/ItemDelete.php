<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StoreInvoiceWrapper.php';
require_once 'Store/dataobjects/StoreInvoiceItemWrapper.php';

/**
 * Delete confirmation page for invoice items
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceItemDelete extends AdminDBDelete
{
	// {{{ private properties

	/** 
	 * @var StoreInvoiceItemWrapper
	 */
	private $invoice_items;

	// }}}
	// {{{ private function getInvoiceItems()

	private function getInvoiceItems()
	{
		if ($this->invoice_items === null) {
			$item_list = $this->getItemList('integer');

			// get invoice items to be deleted
			$sql = sprintf('select id, sku, description, price, invoice
				from InvoiceItem where id in (%s)',
				$item_list);

			$wrapper = SwatDBClassMap::get('StoreInvoiceItemWrapper');
			$this->invoice_items =
				SwatDB::query($this->app->db, $sql, $wrapper);

			// original invoice for each item is needed for locale formatting
			// and for navbar information
			$invoice_sql = 'select id, locale, account from Invoice
				where id in (%s)';

			$invoices = $this->invoice_items->loadAllSubDataObjects(
				'invoice', $this->app->db, $invoice_sql,
				SwatDBClassMap::get('StoreInvoiceWrapper'));
		}

		return $this->invoice_items;
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
			Store::ngettext(
			'One invoice item has been deleted.',
			'%s invoice items have been deleted.', $num),
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

		$dep = new AdminListDependency();
		$dep->setTitle(Store::_('invoice item'), Store::_('invoice items'));

		$entries = array();
		foreach ($this->getInvoiceItems() as $item) {
			$entry = new AdminDependencyEntry();
			$entry->id = $item->id;
			$entry->status_level = AdminDependency::DELETE;

			$title = $item->getDetailedDescription();
			$title.= ' — '.SwatString::moneyFormat($item->price,
				$item->invoice->locale->id);

			$entry->title = $title;
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
		$this->navbar->popEntry();

		$invoice = $this->getInvoiceItems()->getFirst()->invoice;
		$fullname = $invoice->account->fullname;

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry($fullname,
			'Account/Details?id='.$invoice->account->id));

		$this->navbar->addEntry(new SwatNavBarEntry(
			sprintf(Store::_('Invoice %s'), $invoice->id),
			sprintf('Invoice/Details?id=%s', $invoice->id)));

		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Delete Items')));
	}

	// }}}
}

?>
