<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for Locales
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreLocaleDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();
		$item_list = $this->getItemList('text');

		$sql = $this->getProcessSQL();
		$num = SwatDB::exec($this->app->db, sprintf($sql, $item_list));

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One locale has been deleted.', '%d locales have been deleted.',
			$num), SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}
	// {{{ protected function getProcessSQL()

	protected function getProcessSQL()
	{
		return 'delete from Locale where id in (%s)
			and id not in (select locale from Orders)';
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('text');
		$dep = new AdminListDependency();
		$dep->title = Store::_('Locale');
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'Locale', 'text:id', null, 'text:id', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$this->getDependencies($dep, $item_list);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ protected function getDependencies()

	protected function getDependencies($dep, $item_list)
	{
		// dependent orders
		$dep_orders = new AdminSummaryDependency();
		$dep_orders->title = Store::_('Order');
		$dep_orders->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'Orders', 'integer:id', 'text:locale',
			'locale in ('.$item_list.')', AdminDependency::NODELETE);

		$dep->addDependency($dep_orders);
	}

	// }}}
}

?>
