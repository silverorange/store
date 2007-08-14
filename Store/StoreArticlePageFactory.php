<?php

require_once 'Site/SiteArticlePageFactory.php';

/**
 * Resolves and creates article pages in a store web application
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreArticlePageFactory extends SiteArticlePageFactory
{
	// {{{ protected function __construct()

	/**
	 * Creates a StoreArticlePageFactory
	 */
	protected function __construct()
	{
		parent::__construct();

		// set location to load Store page classes from
		$this->class_map['Store'] = 'Store/pages';
	}

	// }}}
	// {{{ protected function checkVisibilty()

	protected function checkVisibilty($page)
	{
		$article = null;
		$path = $page->getPath();

		if ($path !== null) {
			$path_entry = $path->getLast();
			if ($path_entry !== null) {
				$article_id = $path_entry->id;
				$region = $page->app->getRegion();

				$sql = sprintf('select id from EnabledArticleView
					where id = %s and region = %s',
					$page->app->db->quote($article_id, 'integer'),
					$page->app->db->quote($region->id, 'integer'));

				$article = SwatDB::queryOne($page->app->db, $sql);
			}
		}

		return ($article !== null);
	}

	// }}}
	// {{{ protected function instantiateNotVisiblePage()

	protected function instantiateNotVisiblePage(StoreApplication $app,
		SiteLayout $layout)
	{
		require_once 'Store/pages/StoreArticleNotVisiblePage.php';
		$page = new StoreArticleNotVisiblePage($app, $layout);

		return $page;
	}

	// }}}
	// {{{ protected function getArticle()

	/**
	 * Gets an article object from the database
	 *
	 * @param integer $id the database identifier of the article to get.
	 *
	 * @return SiteArticle the specified article or null if no such article
	 *                       exists.
	 */
	protected function getArticle($app, $article_id, $path)
	{
		$article = parent::getArticle($app, $article_id, $path);
		$article->setRegion($app->getRegion());

		return $article;
	}

	// }}}
}

?>
