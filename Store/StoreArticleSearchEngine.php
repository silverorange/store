<?php

require_once 'Site/SiteArticleSearchEngine.php';
require_once 'Site/dataobjects/SiteArticleWrapper.php';

/**
 * An article search engine that is region aware
 *
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreArticleSearchEngine extends SiteArticleSearchEngine
{
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$where_clause = sprintf('where Article.searchable = %s and
			Article.id in
				(select id from VisibleArticleView where region = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		return $where_clause;
	}

	// }}}
}

?>
