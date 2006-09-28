<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';

require_once 'include/StoreConversionRateCellRenderer.php';

/**
 * Report page for Ad
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAdIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->checked_items);

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Ad/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = sprintf('select Ad.id, Ad.title, Ad.shortname,
				Ad.total_referrers, OrderCountByAdView.order_count,
				OrderCountByAdView.conversion_rate
			from Ad
				inner join OrderCountByAdView on OrderCountByAdView.ad = Ad.id
			order by %s',
			$this->getOrderByClause($view, 'createdate desc'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		return $store;
	}

	// }}}
}	

?>
