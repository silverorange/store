<?php

require_once 'Site/SiteArticlePageFactory.php';

/**
 * Resolves and creates article pages in a store web application
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreArticlePageFactory extends SiteArticlePageFactory
{
	// {{{ public function __construct()

	/**
	 * Creates a StoreArticlePageFactory
	 */
	public function __construct(SiteApplication $app)
	{
		parent::__construct($app);

		// set location to load Store page classes from
		$this->page_class_map['Store'] = 'Store/pages';
	}

	// }}}
	// {{{ protected function isVisible()

	protected function isVisible($source, SiteArticle $article)
	{
		$region = $this->app->getRegion();
		$sql = sprintf('select count(id) from EnabledArticleView
			where id = %s and region = %s',
			$this->app->db->quote($article->id, 'integer'),
			$this->app->db->quote($region->id, 'integer'));

		$count = SwatDB::queryOne($this->app->db, $sql);
		return ($count !== 0);
	}

	// }}}
	// {{{ protected function getNotVisiblePage()

	protected function getNotVisiblePage(SiteLayout $layout)
	{
		require_once 'Store/pages/StoreArticleNotVisiblePage.php';
		$page = new SitePage($this->app, $layout);
		return new StoreArticleNotVisiblePage($page);
	}

	// }}}
	// {{{ protected function getArticle()

	/**
	 * Gets an article object from the database
	 *
	 * @param string $path
	 *
	 * @return SiteArticle the specified article or null if no such article
	 *                       exists.
	 */
	protected function getArticle($path)
	{
		$article = parent::getArticle($path);
		$article->setRegion($this->app->getRegion());
		return $article;
	}

	// }}}
}

?>
