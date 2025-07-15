<?php

/**
 * Index page for Articles.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticleIndex extends SiteArticleIndex
{
    /**
     * Cache of regions used by queryRegions().
     *
     * @var RegionsWrapper
     */
    private $regions;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');

        $view = $this->ui->getWidget('index_view');
        $this->ui->getWidget('article_region_action')->db = $this->app->db;
    }

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        $processor = new StoreArticleActionsProcessor($this);
        $processor->process($view, $actions);
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $visibility = $this->ui->getWidget('visibility');
        $visibility->removeOptionsByValue('enable');
        $visibility->removeOptionsByValue('disable');
    }

    protected function buildDetailsToolbar()
    {
        parent::buildDetailsToolbar();

        $regions = $this->queryRegions();
        $region_count = count($regions);

        $prototype_tool_link = $this->ui->getWidget('view_on_site');
        $toolbar = $prototype_tool_link->parent;
        $toolbar->remove($prototype_tool_link);

        foreach ($this->regions as $region) {
            $locale = $region->getFirstLocale();
            if ($locale !== null) {
                $sql = sprintf(
                    'select article from ArticleRegionBinding
					where region = %s and article = %s',
                    $this->app->db->quote($region->id, 'integer'),
                    $this->app->db->quote($this->article->id, 'integer')
                );

                $visible_in_region =
                    (SwatDB::queryOne($this->app->db, $sql) !== null);

                $tool_link = clone $prototype_tool_link;
                $tool_link->id .= '_' . $region->id;

                if ($region_count > 1) {
                    $tool_link->value = $locale->getURLLocale() .
                        $this->article->path;

                    $tool_link->title .= sprintf(' (%s)', $region->title);
                }

                $tool_link->sensitive =
                    ($visible_in_region && $this->article->enabled);

                $toolbar->packEnd($tool_link);
            }
        }
    }

    final protected function queryRegions()
    {
        if ($this->regions === null) {
            $sql = 'select id, title from Region order by id';

            $this->regions = SwatDB::query(
                $this->app->db,
                $sql,
                SwatDBClassMap::get(StoreRegionWrapper::class)
            );
        }

        return $this->regions;
    }
}
