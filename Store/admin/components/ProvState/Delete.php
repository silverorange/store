<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for ProvStates
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreProvStateDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected funtion processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from ProvState where id in (%s)
				and id not in (select provstate from OrderAddress)
				and id not in (select provstate from CatalogRequest)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One province or state has been deleted.',
			'%d provinces and/or states have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->title = Store::_('Province/State');
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'ProvState', 'id', null, 'text:title', 'title',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		// dependent orders
		$dep_orders = new AdminSummaryDependency();
		$dep_orders->title = Store::_('Order Address');
		$dep_orders->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'OrderAddress', 'integer:id', 'integer:provstate',
			'provstate in ('.$item_list.')', AdminDependency::NODELETE);

		$dep->addDependency($dep_orders);

		// dependent catalog requests
		$dep_cat_requests = new AdminSummaryDependency();
		$dep_cat_requests->title = Store::_('Catalogue Order');
		$dep_cat_requests->summary = AdminSummaryDependency::querySummaries(
			$this->app->db, 'CatalogRequest', 'integer:id', 'integer:provstate',
			'provstate in ('.$item_list.')', AdminDependency::NODELETE);

		$dep->addDependency($dep_cat_requests);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
