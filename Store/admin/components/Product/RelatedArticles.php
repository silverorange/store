<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Search page for add related articles to product tool
 *
 * @package   Store
 * @copyright 2007-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductRelatedArticles extends AdminSearch
{
	// {{{ private properties

	protected $ui_xml = 'Store/admin/components/Product/relatedarticles.xml';

	// }}}
	// {{{ private properties

	private $product_id;
	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->product_id = SiteApplication::initVar('product');
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
	// {{{ private function getProductLink()

	private function getProductLink()
	{
		if ($this->category_id === null) {
			$link = sprintf('Product/Details?id=%s', $this->product_id);
		} else {
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product_id, $this->category_id);
		}

		return $link;
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

				$sql = sprintf('insert into ArticleProductBinding
					(product, article)
					select %1$s, Article.id from Article
					where Article.id
						not in (select article from ArticleProductBinding
						where product = %1$s)
					and Article.id in (%2$s)',
					$this->app->db->quote($this->product_id, 'integer'),
					implode(',', $article_list));

				$num = SwatDB::exec($this->app->db, $sql);

				$message = new SwatMessage(sprintf(Store::ngettext(
					'One related article has been added to this product.',
					'%d related articles have been added to this product.',
					$num), SwatString::numberFormat($num)),
					SwatMessage::NOTIFICATION);

				$this->app->messages->add($message);
			}

			$this->app->relocate($this->getProductLink());
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
		$search_form->addHiddenField('product', $this->product_id);
		$search_form->addHiddenField('category', $this->category_id);

		$index_form = $this->ui->getWidget('index_form');
		$index_form->action = $this->source;
		$index_form->addHiddenField('product', $this->product_id);
		$index_form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
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
			// use category navbar
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry)
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
		}

		$product_title = SwatDB::queryOne($this->app->db, sprintf(
			'select title from product where id = %s',
			$this->app->db->quote($this->product_id, 'integer')));

		$this->navbar->addEntry(new SwatNavBarEntry($product_title,
			$this->getProductLink()));

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
