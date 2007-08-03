<?php

require_once 'Store/pages/StoreNotVisiblePage.php';

/**
 * A page for displaying a message if a article is not visible
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticleNotVisiblePage extends StoreNotVisiblePage
{
	// {{{ public properties

	public $article_id;

	// }}}
	// {{{ protected properties

	protected $article;

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$sql = 'select * from Article where id = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->article_id, 'integer'));

		$articles = SwatDB::query($this->app->db, $sql,
			'SiteArticleWrapper');

		$this->article = $articles->getFirst();

		$this->layout->data->title =
			SwatString::minimizeEntities((string)$this->article->title);

		$this->ui->getWidget('content')->content = sprintf(Store::_(
			'%s is not available on our %s store.'),
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

		$sql = sprintf($sql,
			$this->app->db->quote($this->article_id, 'integer'));

		return SwatDB::query($this->app->db, $sql,
			'StoreRegionWrapper');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar($link_prefix = '')
	{
		$this->layout->navbar->addEntries($this->article->getNavBarEntries());
	}

	// }}}
}

?>
