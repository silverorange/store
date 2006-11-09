<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Order page for Products
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryProductOrder extends AdminDBOrder
{
	// {{{ private properties

	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->category_id = SiteApplication::initVar('category');
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		SwatDB::exec($this->app->db,
			'alter table CategoryProductBinding
			disable trigger VisibleProductTrigger');

		$this->saveIndexes();

		SwatDB::exec($this->app->db,
			'alter table CategoryProductBinding
			enable trigger VisibleProductTrigger');
	}

	// }}}
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::query($this->app->db,
			sprintf('update CategoryProductBinding set displayorder = %s
				where category = %s and product = %s',
				$this->app->db->quote($index, 'integer'),
				$this->app->db->quote($this->category_id, 'integer'),
				$this->app->db->quote($id, 'integer')));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$where_clause = sprintf('category = %s',
			$this->app->db->quote($this->category_id, 'integer'));

		$order_widget = $this->ui->getWidget('order');

		$rs = SwatDB::query($this->app->db, sprintf('select id, title 
			from Product
				inner join CategoryProductBinding on 
					Product.id = CategoryProductBinding.product
			where %s
			order by displayorder, title', $where_clause));

		foreach ($rs as $row)
			$order_widget->addOption($row->id, $row->title);

		$sql = sprintf(
			'select sum(displayorder) from CategoryProductBinding where %s',
			$where_clause);

		$sum = $this->app->db->queryOne($sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$order_entry = $this->navbar->popEntry();

		if ($this->category_id !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
				'getCategoryNavbar', array($this->category_id));
			
			foreach ($navbar_rs as $row) {
				$this->title = $row->title;
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
			}
		}

		$this->navbar->addEntry($order_entry);
	}

	// }}}
}
?>
