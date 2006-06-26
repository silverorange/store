<?php

require_once 'Site/SitePageFactory.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreArticlePageFactory extends SitePageFactory
{
	// {{{ protected properties

	protected $default_page_class = 'StoreArticlePage';

	// }}}
	// {{{ public function resolvePage()

	public function resolvePage($app, $source)
	{
		$layout = $this->resolveLayout($app, $source);

		foreach ($this->getPageMap() as $pattern => $class) {
			$regs = array();
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {
				array_shift($regs); //discard full match
				$article_path = array_shift($regs);
				array_unshift($regs, $layout);
				array_unshift($regs, $app);
				$page = $this->instantiatePage($class, $regs);
				$page->setPath($article_path);
				return $page;
			}
		}

		$params = array($app, $layout);
		$page = $this->instantiatePage($this->default_page_class, $params);
		return $page;
	}

	// }}}
}

?>
