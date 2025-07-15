<?php

/**
 * A category search engine.
 *
 * @copyright 2007-2016 silverorange
 */
class StoreCategorySearchEngine extends SiteSearchEngine
{
    protected function getResultWrapperClass()
    {
        return SwatDBClassMap::get(StoreCategoryWrapper::class);
    }

    protected function getSelectClause()
    {
        return 'select Category.id, Category.title, Category.shortname,
				Category.image, c.product_count, c.region as region_id';
    }

    protected function getFromClause()
    {
        $clause = sprintf(
            'from Category
			inner join CategoryVisibleProductCountByRegionCache as c
				on c.category = Category.id and c.region = %s',
            $this->app->db->quote($this->app->getRegion()->id, 'integer')
        );

        if ($this->fulltext_result !== null) {
            $clause .= ' ' .
                $this->fulltext_result->getJoinClause('Category.id', 'category');
        }

        return $clause;
    }

    protected function getOrderByClause()
    {
        if ($this->fulltext_result === null) {
            $clause = sprintf('order by Category.title');
        } else {
            $clause =
                $this->fulltext_result->getOrderByClause('Category.title');
        }

        return $clause;
    }

    protected function getWhereClause()
    {
        return sprintf(
            'where id in
			(select Category from VisibleCategoryView
			where region = %s or region is null)',
            $this->app->db->quote($this->app->getRegion()->id, 'integer')
        );
    }

    protected function getMemcacheNs()
    {
        return 'product';
    }
}
