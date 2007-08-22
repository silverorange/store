<?php

require_once 'Site/admin/components/Article/Search.php';

require_once 'include/StoreArticleActionsProcessor.php';
require_once 'include/StoreArticleRegionAction.php';

/**
 * Search page for Articles
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticleSearch extends SiteArticleSearch
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Article/admin-article-search.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');

		$regions_sql = 'select id, title from Region';
		$regions = SwatDB::query($this->app->db, $regions_sql);
		$search_regions = $this->ui->getWidget('search_regions');
		foreach ($regions as $region) {
			$search_regions->addOption($region->id, $region->title);
			$search_regions->values[] = $region->id;
		}

		$this->ui->getWidget('article_region_action')->db = $this->app->db;
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$processor = new StoreArticleActionsProcessor($this);
		$processor->process($view, $actions);
	}

	// }}}

	// build phase
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$where = parent::getWhereClause();

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

			$this->where = $where;
		}

		return $this->where;
	}

	// }}}
}

?>
