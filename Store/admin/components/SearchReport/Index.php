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
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/SearchReport/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$date = SwatDB::queryOne($this->app->db,
				"select min(creation_date) from NateGoSearchHistory");

		$start = new SwatDate($date);
		$start->convertTZById($this->app->config->date->time_zone);
		$this->ui->getWidget('start_date')->valid_range_start = $start;
		$this->ui->getWidget('end_date')->valid_range_start = $start;

		$now = new SwatDate();
		$now->convertTZById($this->app->config->date->time_zone);
		$this->ui->getWidget('start_date')->valid_range_end = $now;
		$this->ui->getWidget('end_date')->valid_range_end = $now;

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

		$start = $this->ui->getWidget('start_date')->value;
		$end = $this->ui->getWidget('end_date')->value;

		if ($start !== null) {
			$date = new SwatDate($start);
			$date->setTzById($this->app->config->date->time_zone);
			$date->toUTC();

			$where_clause.= sprintf(" and creation_date >= %s",
				$this->app->db->quote($date->getDate(), 'date'));
		}

		if ($end !== null) {
			$date = new SwatDate($end);
			$date->setTzById($this->app->config->date->time_zone);
			$date->toUTC();

			$where_clause.= sprintf(" and creation_date < %s",
				$this->app->db->quote($date->getDate(), 'date'));
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
