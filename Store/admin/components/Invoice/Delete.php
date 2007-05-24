<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';
require_once 'Store/dataobjects/StoreInvoice.php';

/**
 * Delete confirmation page for invoices
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceDelete extends AdminDBDelete
{
	// init phase
	// {{{ protected function getAccount()

	protected function getAccount()
	{
		$class_map = SwatDBClassMap::instance();
		$invoice_class = $class_map->resolveClass('StoreInvoice');
		$invoice = new $invoice_class();
		$invoice->setDatabase($this->app->db);

		if (!$invoice->load($this->getFirstItem()))
			throw new AdminNotFoundException(sprintf(
				Store::_('An invoice with an id of ‘%d’ does not exist.'),
				$this->id));

		return $invoice->account;
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from Invoice where id in (%s)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(
			Store::ngettext('One invoice has been deleted.',
			'%d invoices have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{  protected function relocate()

	protected function relocate()
	{
		if ($this->single_delete) {
			$form = $this->ui->getWidget('confirmation_form');

			if ($form->button->id == 'no_button') {
				// single delete that was cancelled, go back to details page
				parent::relocate();
			} else {
				$this->app->relocate(sprintf('Account/Details?id=%s',
					$this->getAccount()->id));
			}
		} else {
			parent::relocate();
		}
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
		$dep->setTitle(Store::_('invoice'), Store::_('invoices'));

		$sql = sprintf('select id from Invoice where id in (%s)',
			$item_list);

		$entries = array();
		$rows = SwatDB::query($this->app->db, $sql);

		foreach ($rows as $row) {
			$entry = new AdminDependencyEntry();
			$entry->id = $row->id;
			$entry->status_level = AdminDependency::DELETE;
			$entry->title = sprintf(Store::_('Invoice %s'), $row->id);
			$entries[] = $entry;
		}

		$dep->entries = &$entries;

		$this->getDependentItems($dep, $item_list);

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
		$account = $this->getAccount();

		$fullname = $account->fullname;

		$last_entry = $this->navbar->popEntry();

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry($fullname,
			'Account/Details?id='.$account->id));

		if ($this->single_delete) {
			$id = $this->getFirstItem();
			$this->navbar->addEntry(new SwatNavBarEntry(
				sprintf(Store::_('Invoice %s'), $id),
				sprintf('Invoice/Details?id=%s', $id)));
		}

		$this->navbar->addEntry($last_entry);
	}

	// }}}
	// {{{ private function getDependentItems()

	private function getDependentItems($dep, $item_list)
	{
		$dep_items = new AdminSummaryDependency();
		$dep_items->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'InvoiceItem', 'integer:id', 'integer:invoice',
			'invoice in ('.$item_list.')', AdminDependency::DELETE);

		$dep->addDependency($dep_items);
	}

	// }}}
}

?>
