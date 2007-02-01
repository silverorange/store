<?php

require_once 'NateGoSearch/NateGoSearchQuery.php'; 
require_once 'Swat/SwatUI.php';

/**
 * Performs a search on products
 *
 * The same search interface is used in multiple places in the admin and this
 * class keeps the SQL where clause for the interface in the one place.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @see       Store/admin/components/Product/search.xml
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductSearch
{
	// {{{ protected properties

	protected $db;
	protected $ui;
	protected $order_by_clause;
	protected $join_clause;
	protected $where_clause;

	// }}}
	// {{{ public function __construct()

	/**
	 * Performs a search on products
	 *
	 * @param SwatUI $ui the user interface contgaining search parameters.
	 * @param MDB2_Driver_Common $db the database to search.
	 *
	 * @see StoreProductSearch::getJoinClause()
	 * @see StoreProductSearch::getWhereClause()
	 * @see StoreProductSearch::getOrderByClause()
	 */
	public function __construct(SwatUI $ui, MDB2_Driver_Common $db)
	{
		$this->ui = $ui;
		$this->db = $db;

		$keywords = $ui->getWidget('search_keywords')->value;
		if (strlen(trim($keywords)) > 0 &&
			$this->getProductSearchType() !== null) {

			$query = new NateGoSearchQuery($db);
			$query->addDocumentType($this->getProductSearchType());
			$query->addBlockedWords(
				NateGoSearchQuery::getDefaultBlockedWords());

			$result = $query->query($keywords);

			$this->join_clause = sprintf(
				'inner join %1$s on
					%1$s.document_id = Product.id and
					%1$s.unique_id = %2$s and %1$s.document_type = %3$s',
				$result->getResultTable(),
				$db->quote($result->getUniqueId(), 'text'),
				$db->quote($this->getProductSearchType(), 'integer'));

			$this->order_by_clause = sprintf(
				'%1$s.displayorder1, %1$s.displayorder2,
					Product.title, Product.id',
				$result->getResultTable());

		} else {
			$this->join_clause = '';
			$this->order_by_clause = 'Product.title, Product.id';
		}

		$this->buildWhereClause();
	}

	// }}}
	// {{{ public function getOrderByClause()

	public function getOrderByClause()
	{
		return $this->order_by_clause;
	}

	// }}}
	// {{{ public function getJoinClause()

	public function getJoinClause()
	{
		return $this->join_clause;
	}

	// }}}
	// {{{ public function getWhereClause()

	public function getWhereClause()
	{
		return $this->where_clause;
	}

	// }}}
	// {{{ protected function getProductSearchType()

	/**
	 * Gets the search type for products for this web-application
	 *
	 * @return integer the search type for products for this web-application or
	 *                  null if fulltext searching is not implemented for the
	 *                  current application.
	 */
	protected function getProductSearchType()
	{
		return null;
	}

	// }}}
	// {{{ protected function buildWhereClause()

	/**
	 * Builds the SQL where clause for a product search
	 *
	 * @see StoreProductSearch::getWhereClause()
	 */
	protected function buildWhereClause()
	{
		$where = '1 = 1';

		// keywords are included in the where clause if fulltext searching is
		// turned off, we need to check length of the trimmed value because if
		// its thats what AdminSearchClause does to it
		$keywords_value = $this->ui->getWidget('search_keywords')->value;
		if ($this->getProductSearchType() === null &&
			strlen(trim($keywords_value)) > 0) {
			$where.= ' and (';

			$clause = new AdminSearchClause('title');
			$clause->table = 'Product';
			$clause->value = $keywords_value;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->db, '');

			$clause = new AdminSearchClause('bodytext');
			$clause->table = 'Product';
			$clause->value = $keywords_value;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->db, 'or');

			$where.= ') ';
		}

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

		$this->where_clause = $where;
	}

	// }}}
}

?>
