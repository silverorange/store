<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Store/exceptions/StoreNotFoundException.php';
require_once 'Store/layouts/StoreLayout.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreArticlePageFactory
{
	// {{{ protected properties

	protected $page_class_path = '../include/pages';
	protected $default_page_class = 'ArticlePage';

	// }}}
	// {{{ abstract public static function instance()

	/**
	 * Gets the singleton instance of the class-mapping object
	 *
	 * @return StoreDataObjectClassMap the singleton instance of the class-
	 *                                  mapping object.
	 */
	abstract public static function instance();

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
	// {{{ protected function instantiatePage()

	public function instantiatePage($class, $params)
	{
		$class_file = sprintf('%s/%s.php', $this->page_class_path, $class);
		require_once $class_file;

		$page = call_user_func_array(
			array(new ReflectionClass($class), 'newInstance'), $params);

		return $page;
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array();
	}

	// }}}
	// {{{ protected function resolveLayout()

	protected function resolveLayout($app, $source)
	{
		return new StoreLayout($app);
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Creates a StoreAritclePageFactory object
	 *
	 * The constructor is private as this class uses the singleton pattern.
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
