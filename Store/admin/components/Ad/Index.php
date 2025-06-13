<?php

/**
 * Report page for Ad.
 *
 * Store also displays order conversion rate.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdIndex extends SiteAdIndex
{
    // init phase

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    // build phase

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = sprintf(
            'select Ad.*,
				coalesce(OrderCountByAdView.order_count, 0) as order_count,
				OrderCountByAdView.conversion_rate
			from Ad
				left outer join OrderCountByAdView on
					OrderCountByAdView.ad = Ad.id
			order by %s',
            $this->getOrderByClause($view, 'createdate desc')
        );

        return SwatDB::query($this->app->db, $sql);
    }
}
