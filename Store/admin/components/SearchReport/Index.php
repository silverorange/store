<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatMoneyCellRenderer.php';
require_once 'Swat/SwatNumericCellRenderer.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';

/**
 * Search report index page
 *
 * @package   Store
 * @copyright 2007-2008 silverorange
 */
class StoreSearchReportIndex extends AdminIndex
{
	// {{{ class constants

	const MAX_RESULTS = 50;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');

		$date = new SwatDate();
		$months = array();
		for ($i = 1; $i <= 12; $i++) {
			$date->setMonth($i);
			$months[$i] = $date->format('%B');
		}

		$this->ui->getWidget('search_month')->addOptionsByArray($months);
		$first_year = SwatDB::queryOne($this->app->db, sprintf(
				"select date_part('year', min(convertTZ(creation_date, %s))) ".
				"from NateGoSearchHistory",
				$this->app->db->quote($this->app->config->date->time_zone,
					'text')));

		$date = new SwatDate();
		$years = array();
		for ($i = $first_year; $i <= $date->getYear(); $i++) {
			$years[$i] = $i;
		}

		$this->ui->getWidget('search_year')->addOptionsByArray($years);

		$view = $this->ui->getWidget('results_view');
		$renderer = $view->getColumn('keywords')->getFirstRenderer();
		$renderer->link = sprintf('%ssearch?keywords=%%s',
			str_replace('%', '%%', $this->app->getFrontendBaseHref()));

		$view = $this->ui->getWidget('no_results_view');
		$renderer = $view->getColumn('keywords')->getFirstRenderer();
		$renderer->link = sprintf('%ssearch?keywords=%%s',
			str_replace('%', '%%', $this->app->getFrontendBaseHref()));
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$where_clause = '1 = 1';

		$month = $this->ui->getWidget('search_month')->value;
		if ($month !== null) {
			$where_clause.= sprintf(" and date_part('month', ".
				"convertTZ(creation_date, %s)) = %s",
				$this->app->db->quote($this->app->config->date->time_zone, 'text'),
				$this->app->db->quote($month, 'integer'));
		}

		$year = $this->ui->getWidget('search_year')->value;
		if ($year !== null) {
			$where_clause.= sprintf(" and date_part('year', ".
				"convertTZ(creation_date, %s)) = %s",
				$this->app->db->quote($this->app->config->date->time_zone, 'text'),
				$this->app->db->quote($year, 'integer'));
		}

		switch ($view->id) {
		case 'results_view':
			$sql = sprintf('select count(id) as count, keywords
				from NateGoSearchHistory
				where document_count > 0 and %s
				group by keywords
				order by count desc
				limit %s',
				$where_clause,
				$this->app->db->quote(self::MAX_RESULTS, 'integer'));

			$store = SwatDB::query($this->app->db, $sql);
			break;
		case 'no_results_view':
			$sql = sprintf('select count(id) as count, keywords
				from NateGoSearchHistory
				where document_count = 0 and %s
				group by keywords
				order by count desc
				limit %s',
				$where_clause,
				$this->app->db->quote(self::MAX_RESULTS, 'integer'));

			$store = SwatDB::query($this->app->db, $sql);
			break;
		}

		return $store;
	}

	// }}}
}

?>
