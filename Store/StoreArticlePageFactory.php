<?php

require_once 'Site/SitePageFactory.php';
require_once 'Store/StoreArticlePath.php';

/**
 * Resolves and creates article pages in a store web application
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreArticlePageFactory extends SitePageFactory
{
	// {{{ protected properties

	/**
	 * The name of the default class to use when instantiating resolved pages
	 *
	 * This must be either {@link StoreArticlePage} or a subclass of
	 * StoreArticlePage.
	 *
	 * @var string
	 */
	protected $default_page_class = 'StoreArticlePage';

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param StoreApplication $app the web application for which the page is
	 *                               being resolved.
	 * @param string $source the source string for which to get the page.
	 *
	 * @return StoreArticlePage the page for the given source string.
	 */
	public function resolvePage(SiteWebApplication $app, $source)
	{
		$layout = $this->resolveLayout($app, $source);
		$article_path = $source;

		$page = null;

		foreach ($this->getPageMap() as $pattern => $class) {
			$regs = array();
			$pattern = str_replace('@', '\@', $pattern); // escape delimiters
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {
				array_shift($regs); // discard full match string
				$article_path = array_shift($regs);
				array_unshift($regs, $layout);
				array_unshift($regs, $app);

				$page = $this->instantiatePage($class, $regs);
				break;
			}
		}

		if ($page === null) {
			// not found in page map so instantiate default page
			$params = array($app, $layout);
			$page = $this->instantiatePage($this->default_page_class, $params);
		}

		$article_id = $this->findArticle($app, $article_path);

		if ($article_id === null)
			throw new SiteNotFoundException(
				sprintf('Article not found for path ‘%s’',
					$article_path));

		$article_path = new StoreArticlePath($app, $article_id);

		$page->article_id = $article_id;
		$page->setPath($article_path);

		if (!$page->isVisibleInRegion($app->getRegion())) {
			$page = $this->instantiateNotVisiblePage($app, $layout);
			$page->article_id = $article_id;
			$page->setPath($article_path);
		}

		return $page;
	}

	// }}}
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
	// {{{ protected function instantiateNotVisiblePage()

	protected function instantiateNotVisiblePage(StoreApplication $app,
		SiteLayout $layout)
	{
		require_once 'Store/pages/StoreArticleNotVisiblePage.php';
		$page = new StoreArticleNotVisiblePage($app, $layout);

		return $page;
	}

	// }}}
	// {{{ protected function findArticle()

	/**
	 * Gets an article id from the given article path
	 *
	 * @param StoreApplication $app
	 * @param string $path
	 *
	 * @return integer the database identifier corresponding to the given
	 *                  article path or null if no such identifier exists.
	 */
	protected function findArticle(StoreApplication $app, $path)
	{
		// trim at 254 to prevent database errors
		$path = substr($path, 0, 254);
		$sql = sprintf('select findArticle(%s)',
			$app->db->quote($path, 'text'));

		$article_id = SwatDB::queryOne($app->db, $sql);
		return $article_id;
	}

	// }}}
}

?>
