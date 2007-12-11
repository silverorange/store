<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Site/admin/components/Ad/Index.php';
require_once 'Store/admin/components/Ad/include/StoreConversionRateCellRenderer.php';

/**
 * Report page for Ad
 *
 * Store also displays order conversion rate.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdIndex extends SiteAdIndex
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		parent::__construct($app, $layout);
		$this->ui_xml = dirname(__FILE__).'/index.xml';
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select Ad.id, Ad.title, Ad.shortname,
				Ad.total_referrers, OrderCountByAdView.order_count,
				cast(OrderCountByAdView.conversion_rate as numeric(5,2))
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
