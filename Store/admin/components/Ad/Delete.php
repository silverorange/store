<?php

require_once 'Site/admin/components/Ad/Delete.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for Ads
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdDelete extends SiteAdDelete
{
	// process phase
	// {{{ protected function getDeleteSql()

	protected function getDeleteSql()
	{
		$item_list = $this->getItemList('text');
		$sql = sprintf('delete from Ad where id in (%s) and
			id not in (select ad from Orders where ad is not null)',
			$item_list);

		return $sql;
	}

	// }}}

	// build phase
	// {{{ protected function getDependencies()

	protected function getDependencies()
	{
		$dep = parent::getDependencies();

		$item_list = $this->getItemList('integer');

		$dep_orders = new AdminSummaryDependency();
		$dep_orders->setTitle(Store::_('order'), Store::_('orders'));
		$dep_orders->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'Orders', 'integer:id', 'integer:ad',
			'ad in ('.$item_list.')', AdminDependency::NODELETE);

		$dep->addDependency($dep_orders);

		return $dep;
	}

	// }}}
}

?>
