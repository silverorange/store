<?php

/**
 * A page for displaying a message if a article is not visible.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticleNotVisiblePage extends StoreNotVisiblePage
{
    // {{{ public function setArticle()

    public function setArticle(SiteArticle $article)
    {
        $this->article = $article;
    }

    // }}}
    // {{{ protected properties

    /**
     * @var SiteArticle
     */
    protected $article;

    // }}}

    // build phase
    // {{{ protected function buildInternal()

    protected function buildInternal()
    {
        $this->layout->data->title =
            SwatString::minimizeEntities((string) $this->article->title);

        $this->ui->getWidget('content')->content = sprintf(
            Store::_(
                '%s is not available on our %s store.'
            ),
            SwatString::minimizeEntities($this->article->title),
            SwatString::minimizeEntities($this->app->getRegion()->title)
        );
    }

    // }}}
    // {{{ protected function getAvailableRegions()

    protected function getAvailableRegions()
    {
        $sql = 'select Region.id, title from Region
			inner join EnabledArticleView
				on EnabledArticleView.region = Region.id
			where EnabledArticleView.id = %s';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->article->id, 'integer')
        );

        return SwatDB::query(
            $this->app->db,
            $sql,
            'StoreRegionWrapper'
        );
    }

    // }}}
    // {{{ protected function buildNavBar()

    protected function buildNavBar($link_prefix = '')
    {
        if (isset($this->layout->navbar)) {
            $this->layout->navbar->addEntries(
                $this->article->getNavBarEntries()
            );
        }
    }

    // }}}
}
