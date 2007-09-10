<?php

require_once 'Site/admin/components/Article/Index.php';
require_once 'Store/dataobjects/StoreArticle.php';

require_once 'include/StoreArticleActionsProcessor.php';
require_once 'include/StoreArticleRegionAction.php';
require_once 'include/StoreArticleVisibilityCellRenderer.php';

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
	protected $ui_xml = 'Store/admin/components/Article/index.xml';

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

		$visibility = $this->ui->getWidget('visibility');
		$visibility->removeOptionsByValue('enable');
		$visibility->removeOptionsByValue('disable');
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = 'select Article.id,
					Article.title, 
					Article.show,
					Article.searchable,
					ArticleChildCountView.child_count
				from Article
					left outer join ArticleChildCountView on
						ArticleChildCountView.article = Article.id
				where Article.parent %s %s
				order by %s';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view, 
				'Article.displayorder, Article.title', 'Article'));
		
		$rs = SwatDB::query($this->app->db, $sql);

		$view = $this->ui->getWidget('index_view');
		$view->getColumn('visibility')->getFirstRenderer()->db =
			$this->app->db;

		if (count($rs) < 2)
			$this->ui->getWidget('articles_order')->sensitive = false;

		return $rs;
	}

	// }}}
}

?>
