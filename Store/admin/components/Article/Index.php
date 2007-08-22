<?php

require_once 'Site/admin/components/Article/Index.php';

require_once 'include/StoreArticleActionsProcessor.php';
require_once 'include/StoreArticleRegionAction.php';

/**
 * Index page for Articles
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticleIndex extends SiteArticleIndex 
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Article/admin-article-index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		
		$view = $this->ui->getWidget('index_view');
		$this->ui->getWidget('article_region_action')->db = $this->app->db;
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$processor = new StoreArticleActionsProcessor($this);
		$processor->process($view, $actions);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal() 
	{
		parent::buildInternal();
	}

	// }}}
}

?>
