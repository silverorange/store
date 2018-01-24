<?php

/**
 * Report page for Ad
 *
 * Store also displays order conversion rate.
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdIndex extends SiteAdIndex
{
	// init phase
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/index.xml';
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select Ad.*,
				coalesce(OrderCountByAdView.order_count, 0) as order_count,
				OrderCountByAdView.conversion_rate
			from Ad
				left outer join OrderCountByAdView on
					OrderCountByAdView.ad = Ad.id
			order by %s',
			$this->getOrderByClause($view, 'createdate desc'));

		$rs = SwatDB::query($this->app->db, $sql);

		return $rs;
	}

	// }}}
}

?>
