<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'include/ArticleActionsProcessor.php';
require_once 'include/ArticleRegionAction.php';
require_once 'include/VisibilityCellRenderer.php';

/**
 * Search page for Articles
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreArticleSearch extends AdminSearch
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/search.xml');

		$regions_sql = 'select id, title from Region';
		$regions = SwatDB::query($this->app->db, $regions_sql);
		$search_regions = $this->ui->getWidget('search_regions');
		foreach ($regions as $region) {
			$search_regions->options[$region->id] = $region->title;
			$search_regions->values[] = $region->id;
		}

		$this->ui->getWidget('article_region_action')->db = $this->app->db;

		$this->navbar->createEntry('Search');
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();

		if ($pager->getCurrentPage() > 0) {
			$disclosure = $this->ui->getWidget('search_disclosure');
			$disclosure->open = false;
		}
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$processor = new ArticleActionsProcessor($this);
		$processor->process($view, $actions);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal() 
	{
		parent::buildInternal();

		$this->ui->getWidget('visibility')->addOptionsByArray(
			ArticleActionsProcessor::getActions());
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		static $where = null;

		if ($where !== null)
			return $where;

		$where = '1=1';
	
		$clause = new AdminSearchClause('title');
		$clause->value = $this->ui->getWidget('search_title')->value;
		$clause->operator = $this->ui->getWidget('search_title_op')->value;
		$where.= $clause->getClause($this->app->db);

		$search_regions = $this->ui->getWidget('search_regions');
		foreach ($search_regions->options as $value => $title) {
			if (in_array($value, $search_regions->values)) {
				$where.= sprintf(' and id in
					(select article from ArticleRegionBinding
					where region = %s)',
					$this->app->db->quote($value, 'integer'));
			} else {
				$where.= sprintf(' and id not in
					(select article from ArticleRegionBinding
					where region = %s)',
					$this->app->db->quote($value, 'integer'));
			}
		}

		$clause = new AdminSearchClause('boolean:show');
		$clause->value = 
			$this->ui->getWidget('search_show')->getValueAsBoolean();
		$where.= $clause->getClause($this->app->db);

		$clause = new AdminSearchClause('boolean:searchable');
		$clause->value = 
			$this->ui->getWidget('search_searchable')->getValueAsBoolean();

		$where.= $clause->getClause($this->app->db);

		return $where;
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = sprintf('select count(id) from Article where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records =	SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select Article.id,
					Article.title, 
					Article.show,
					Article.searchable
				from Article
				where %s
				order by %s';

		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'Article.title', 'Article'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		$this->ui->getWidget('results_frame')->visible = true;
		$view = $this->ui->getWidget('index_view');
		$view->getColumn('visibility')->getRendererByPosition()->db =
			$this->app->db;
		
		if ($store->getRowCount() != 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');

		return $store;
	}

	// }}}
}

?>
