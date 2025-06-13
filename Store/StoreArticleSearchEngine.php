<?php

/**
 * An article search engine that is region aware.
 *
 * @copyright 2007-2016 silverorange
 */
class StoreArticleSearchEngine extends SiteArticleSearchEngine
{
    protected function getWhereClause()
    {
        return sprintf(
            'where Article.searchable = %s and
			Article.id in
				(select id from VisibleArticleView where region = %s)',
            $this->app->db->quote(true, 'boolean'),
            $this->app->db->quote($this->app->getRegion()->id, 'integer')
        );
    }
}
