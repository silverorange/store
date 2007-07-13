<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Search page for Featured Products
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryFeaturedProducts extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Store/admin/components/Category/featuredproducts.xml';

	// }}}
	// {{{ private properties

	private $parent;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->parent = SiteApplication::initVar('parent');
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

			if (count($view->getSelection()) > 0) {

				$product_list = array();
				foreach ($view->getSelection() as $item)
					$product_list[] = $this->app->db->quote($item, 'integer');

				$sql = sprintf('insert into CategoryFeaturedProductBinding
						(category, product)
					select %s, Product.id from Product
					where Product.id not in
						(select product from CategoryFeaturedProductBinding
							where category = %s)
						and Product.id in (%s)',
					$this->app->db->quote($this->parent, 'integer'),
					$this->app->db->quote($this->parent, 'integer'),
					implode(',', $product_list));

				$num = SwatDB::exec($this->app->db, $sql);

				$message = new SwatMessage(sprintf(Store::ngettext(
					'One featured product has been updated.',
					'%s featured products have been updated.', $num),
					SwatString::numberFormat($num)),
					SwatMessage::NOTIFICATION);

				$this->app->messages->add($message);
			}

			$this->app->relocate('Category/Index?id='.$this->parent);
		}
	}
	// }}}

	// build phase
	// {{{ protected function buildInternal()
	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildNavBar();

		$parent = $this->app->db->quote($this->parent, 'integer');

		$rs = SwatDB::executeStoredProc($this->app->db, 'getCategoryTree',
			array($parent));

		$tree = SwatDB::getDataTree($rs, 'title', 'id', 'levelnum');

		$category_flydown = $this->ui->getWidget('category_flydown');
		$category_flydown->setTree($tree);
		$category_flydown->show_blank = false;

		$search_form = $this->ui->getWidget('search_form');
		$search_form->action = $this->source;
		$search_form->addHiddenField('parent', $this->parent);

		$index_form = $this->ui->getWidget('index_form');
		$index_form->action = $this->source;
		$index_form->addHiddenField('parent', $this->parent);
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = 'select distinct Product.id, Product.title
				from Product
				inner join CategoryProductBinding on
					Product.id = CategoryProductBinding.product
				inner join getCategoryDescendents(%s) as
					category_descendents on
					category_descendents.descendent =
						CategoryProductBinding.category
				where category_descendents.category = %s
				order by %s';

		$category_flydown = $this->ui->getWidget('category_flydown');

		if ($category_flydown->value === null)
			$category_flydown->value = $this->parent;

		$sql = sprintf($sql,
			$this->app->db->quote($category_flydown->value, 'integer'),
			$this->app->db->quote($category_flydown->value, 'integer'),
			$this->getOrderByClause($view,
				'Product.title, Product.id'));

		$store = SwatDB::query($this->app->db, $sql);

		return $store;
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		if ($this->parent !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->parent));

			foreach ($navbar_rs as $row)
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Featured Products')));
	}

	// }}}
}

?>
