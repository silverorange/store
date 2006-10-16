<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';

require_once 'include/StoreArticleActionsProcessor.php';
require_once 'include/StoreArticleRegionAction.php';
require_once 'include/StoreArticleVisibilityCellRenderer.php';

/**
 * Index page for Articles
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreArticleIndex extends AdminIndex 
{
	// {{{ private properties

	private $id = null;
	private $parent = null;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
		
		$this->id = SiteApplication::initVar('id');
		
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
	// {{{ protected function getTableStore()

	protected function getTableStore($view) 
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
		
		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		$view = $this->ui->getWidget('index_view');
		$view->getColumn('visibility')->getFirstRenderer()->db =
			$this->app->db;

		if ($store->getRowCount() < 2)
			$this->ui->getWidget('articles_order')->sensitive = false;

		return $store;
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal() 
	{
		parent::buildInternal();

		$articles_frame = $this->ui->getWidget('articles_frame');

		if ($this->id != 0) {
			// show the detail frame
			$details_frame = $this->ui->getWidget('details_frame');
			$details_frame->visible = true;
			
			// move the articles frame inside of the detail frame
			$articles_frame->parent->remove($articles_frame);
			$details_frame->add($articles_frame);
			$articles_frame->title = Store::_('Sub-Articles');
			$this->ui->getWidget('articles_new')->title =
				Store::_('New Sub-Article');

			$this->buildDetails();
		}

		$tool_value = ($this->id === null) ? '' : '?parent='.$this->id;	
		$this->ui->getWidget('articles_toolbar')->setToolLinkValues(
			$tool_value);

		$this->ui->getWidget('visibility')->addOptionsByArray(
			StoreArticleActionsProcessor::getActions());
	}

	// }}}
	// {{{ protected function buildDetails()

	protected function buildDetails() 
	{
		$details_block = $this->ui->getWidget('details_block');
		$details_view = $this->ui->getWidget('details_view');
		$details_frame = $this->ui->getWidget('details_frame');

		// set default time zone
		$createdate_field = $details_view->getField('createdate');
		$createdate_renderer = $createdate_field->getFirstRenderer();
		$createdate_renderer->display_time_zone =
			$this->app->default_time_zone;

		$modified_date_field = $details_view->getField('modified_date');
		$modified_date_renderer =
			$modified_date_field->getRendererByPosition();

		$modified_date_renderer->display_time_zone =
			$this->app->default_time_zone;

		$details_view->getField('visibility')->getFirstRenderer()->db =
			$this->app->db;

		$fields = array('id', 'title', 'shortname', 'description', 'bodytext',
			'show', 'parent', 'createdate', 'modified_date', 'searchable');

		$row = SwatDB::queryRowFromTable($this->app->db, 'Article', $fields, 
			'id' , $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('Article with id ‘%s’ not found.'),
					$this->id));

		if ($row->bodytext !== null)
			$row->bodytext = SwatString::condense(SwatString::toXHTML(
				$row->bodytext));

		if ($row->description !== null)
			$row->description = SwatString::condense(SwatString::toXHTML(
				$row->description));

		$details_frame->title = Store::_('Article');
		$details_frame->subtitle = $row->title;
		$details_view->data = &$row;

		// set link id
		$this->ui->getWidget('details_toolbar')->setToolLinkValues($this->id);

		// build navbar
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Articles'),
			'Article'));

		if ($row->parent != null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
				'getArticleNavBar', array($row->parent));

			foreach ($navbar_rs as $elem)
				$this->navbar->addEntry(new SwatNavBarEntry($elem->title,
					'Article/Index?id='.$elem->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry($row->title));
	}

	// }}}
}

?>
