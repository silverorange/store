<?php

/**
 * Resolves and creates article pages in a store web application.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreArticlePageFactory extends SiteArticlePageFactory
{
    /**
     * Creates a StoreArticlePageFactory.
     */
    public function __construct(SiteApplication $app)
    {
        parent::__construct($app);

        // set location to load Store page classes from
        $this->page_class_map['Store'] = 'Store/pages';
    }

    protected function isVisible(SiteArticle $article, string $source): bool
    {
        $region = $this->app->getRegion();
        $sql = sprintf(
            'select count(id) from EnabledArticleView
			where id = %s and region = %s',
            $this->app->db->quote($article->id, 'integer'),
            $this->app->db->quote($region->id, 'integer')
        );

        if ($this->app->hasModule('SiteMultipleInstanceModule')) {
            $instance = $this->app->instance->getInstance();
            if ($instance !== null) {
                $sql .= sprintf(
                    ' and (instance is null or instance = %s)',
                    $this->app->db->quote($instance->id, 'integer')
                );
            }
        }

        $count = SwatDB::queryOne($this->app->db, $sql);

        return $count !== 0;
    }

    protected function getNotVisiblePage(
        SiteArticle $article,
        SiteLayout $layout
    ): SiteAbstractPage {
        $page = new SitePage($this->app, $layout);
        $page = $this->decorate($page, 'StoreArticleNotVisiblePage');
        $page->setArticle($article);

        return $page;
    }

    /**
     * Gets an article object from the database.
     *
     * @param string $path
     *
     * @return SiteArticle the specified article or null if no such article
     *                     exists
     */
    protected function getArticle($path): SiteArticle
    {
        $article = parent::getArticle($path);
        $article->setRegion($this->app->getRegion());

        return $article;
    }
}
