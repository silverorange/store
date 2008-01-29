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
 * @package   VanBourgondien
 * @copyright 2007 silverorange
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
		switch ($view->id) {
		case 'results_view':
			$sql = sprintf('select count(id) as count, keywords
				from NateGoSearchHistory
				where document_count > 0
				group by keywords
				order by count desc
				limit %s',
				$this->app->db->quote(self::MAX_RESULTS, 'integer'));

			$store = SwatDB::query($this->app->db, $sql);
			break;
		case 'no_results_view':
			$sql = sprintf('select count(id) as count, keywords
				from NateGoSearchHistory
				where document_count = 0
				group by keywords
				order by count desc
				limit %s',
				$this->app->db->quote(self::MAX_RESULTS, 'integer'));

			$store = SwatDB::query($this->app->db, $sql);
			break;
		}

		return $store;
	}

	// }}}
}

?>
