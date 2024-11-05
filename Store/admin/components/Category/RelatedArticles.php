<?php

/**
 * Search page for add related articles to category tool
 *
 * @package   Store
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryRelatedArticles extends AdminSearch
{
	// {{{ private properties

	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());
		$this->category_id = SiteApplication::initVar('category');

		$regions_sql = 'select id, title from Region';
		$regions = SwatDB::query($this->app->db, $regions_sql);
		$search_regions = $this->ui->getWidget('search_regions');
		foreach ($regions as $region) {
			$search_regions->addOption($region->id, $region->title);
			$search_regions->values[] = $region->id;
		}
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/relatedarticles.xml';
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('index_form');

		if ($form->isProcessed()) {
			$view = $this->ui->getWidget('index_view');

			if (count($view->checked_items) != 0) {

				$article_list = array();
				foreach ($view->checked_items as $item)
					$article_list[] = $this->app->db->quote($item, 'integer');

				$sql = sprintf('insert into ArticleCategoryBinding
					(category, article)
					select %s, Article.id from Article
					where Article.id
						not in (select article from ArticleCategoryBinding
						where category = %s)
					and Article.id in (%s)',
					$this->app->db->quote($this->category_id, 'integer'),
					$this->app->db->quote($this->category_id, 'integer'),
					implode(',', $article_list));

				$num = SwatDB::exec($this->app->db, $sql);

				$message = new SwatMessage(sprintf(Store::ngettext(
					'One related article has been added to this category.',
					'%s related articles have been added to this category.',
					$num), SwatString::numberFormat($num)),
					'notice');

				$this->app->messages->add($message);

				if (isset($this->app->memcache))
					$this->app->memcache->flushNs('product');
			}

			$this->app->relocate('Category/Index?id='.$this->category_id);
		}

		$pager = $this->ui->getWidget('pager');
		$pager->process();

		if ($pager->getCurrentPage() > 0) {
			$disclosure = $this->ui->getWidget('search_frame');
			$disclosure->open = false;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$search_frame = $this->ui->getWidget('search_frame');
		$search_frame->title = Store::_('Search for Articles to Relate');

		$search_form = $this->ui->getWidget('search_form');
		$search_form->action = $this->getRelativeURL();
		$search_form->addHiddenField('category', $this->category_id);

		$index_form = $this->ui->getWidget('index_form');
		$index_form->action = $this->source;
		$index_form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view): SwatDBDefaultRecordsetWrapper
	{
		$sql = sprintf('select count(id) from Article where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select id, title from Article where %s order by %s';
		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'title'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$rs = SwatDB::query($this->app->db, $sql);

		$this->ui->getWidget('results_frame')->visible = true;

		if (count($rs) > 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Store::_('result'),
					Store::_('results'));

		return $rs;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->category_id !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($navbar_rs as $row) {
				$this->title = $row->title;
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
			}
		}

		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Add Articles')));
	}

	// }}}
	// {{{ private function getWhereClause()

	private function getWhereClause()
	{
		$where = '1 = 1';

		if ($this->ui->getWidget('search_keywords')->value != null) {

			$where.= ' and ( ';

			$clause = new AdminSearchClause('title');
			$clause->table = 'Article';
			$clause->value = $this->ui->getWidget('search_keywords')->value;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, '');

			$clause = new AdminSearchClause('bodytext');
			$clause->table = 'Article';
			$clause->value = $this->ui->getWidget('search_keywords')->value;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'or');

			$where.= ') ';
		}

			$search_regions = $this->ui->getWidget('search_regions');
		foreach ($search_regions->options as $option) {
			if (in_array($option->value, $search_regions->values)) {
				$where.= sprintf(' and id in
					(select article from ArticleRegionBinding
					where region = %s)',
					$this->app->db->quote($option->value, 'integer'));
			} else {
				$where.= sprintf(' and id not in
					(select article from ArticleRegionBinding
					where region = %s)',
					$this->app->db->quote($option->value, 'integer'));
			}
		}

		return $where;
	}

	// }}}
}

?>
