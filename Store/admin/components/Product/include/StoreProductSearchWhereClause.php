<?php

/**
 * Gets the where clause for a product search
 *
 * The same search interface is used in multiple places in the admin and this
 * class keeps the SQL where clause for the interface in the one place.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @see       Store/admin/components/Product/search.xml
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductSearchWhereClause
{
	private $ui;
	private $db;

	public function __construct(SwatUI $ui, MDB2_Driver_Common $db)
	{
		$this->ui = $ui;
		$this->db = $db;
	}

	/**
	 * Gets the SQL where clause for a product search
	 *
	 * @return string the SQL where clause for a product search.
	 */
	public function getWhereClause()
	{
		$where = '1=1';

		// title
		$clause = new AdminSearchClause('title');
		$clause->table = 'Product';
		$clause->value = $this->ui->getWidget('search_title')->value;
		$clause->operator = $this->ui->getWidget('search_title_op')->value;
		$where.= $clause->getClause($this->db);

		// sku
		$clause = new AdminSearchClause('sku');
		$clause->table = 'ItemView';
		$clause->value = $this->ui->getWidget('search_item')->value;
		$clause->operator = $this->ui->getWidget('search_item_op')->value;
		$item_where = $clause->getClause($this->db, '');

		if (strlen($item_where))
			$where.= sprintf(' and Product.id in (
				select ItemView.product from ItemView where %s)', $item_where);

		// category
		$category = $this->ui->getWidget('search_category')->value;

		if ($category == -1) {
			$where.= ' and Product.id not in (
				select product from CategoryProductBinding)';
		} else {
			$clause = new AdminSearchClause('category');
			$clause->value = $category;
			$clause->table = 'category_descendents';
			$category_descendent_where = $clause->getClause($this->db, '');

			if (strlen($category_descendent_where)) {
				$where.= sprintf(' and Product.id in (select product from
					CategoryProductBinding
					inner join getCategoryDescendents(%s) as
						category_descendents
						on category_descendents.descendent =
							CategoryProductBinding.category
					where %s)',
					$this->db->quote($category, 'integer'),
					$category_descendent_where);
			}
		}

		// catalog
		$catalog_selector = $this->ui->getWidget('catalog_selector');

		$where.= sprintf(' and Product.catalog in (%s)',
			$catalog_selector->getSubQuery());

		return $where;
	}
}

?>
