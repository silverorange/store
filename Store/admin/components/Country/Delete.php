<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

require_once 'include/StoreCountryDependency.php';
require_once 'include/StoreProvStateDependency.php';
require_once 'include/StoreAddressDependency.php';

/**
 * Delete confirmation page for Countries
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCountryDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('text');

		$sql = sprintf('delete from Country where id in (%s)
			and id not in (select country from AccountAddress)
			and id not in (select country from OrderAddress)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One country has been deleted.', '%s countries have been deleted.',
			$num), SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('text');

		$dep = new StoreCountryDependency();
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Country', 'text:id', null, 'text:title', 'title',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		// dependent order addresses
		$orders_billing_dependency = new StoreAddressDependency();
		$orders_billing_dependency->title = Store::_('order');
		$orders_billing_dependency->summaries =
			AdminSummaryDependency::querySummaries(
			$this->app->db, 'OrderAddress', 'integer:id', 'text:country', 
			'country in ('.$item_list.')', AdminDependency::NODELETE);

		$dep->addDependency($orders_billing_dependency);

		// dependent account addresses
		$addresses_dependency = new StoreAddressDependency();
		$addresses_dependency->title = Store::_('account');
		$addresses_dependency->summaries =
			AdminSummaryDependency::querySummaries($this->app->db,
			'AccountAddress', 'integer:id', 'text:country',
			'country in ('.$item_list.')', AdminDependency::NODELETE);

		$dep->addDependency($addresses_dependency);

		$provstates_dependency = new StoreProvStateDependency();
		$provstates_dependency->entries =
			AdminListDependency::queryEntries($this->app->db,
			'ProvState', 'integer:id', 'text:country', 'text:title', 'title',
			'country in ('.$item_list.')', AdminDependency::DELETE);

		$dep->addDependency($provstates_dependency);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
