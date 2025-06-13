<?php

/**
 * Performs a search on products.
 *
 * The same search interface is used in multiple places in the admin and this
 * class keeps the SQL where clause for the interface in the one place.
 *
 * @copyright 2006-2016 silverorange
 *
 * @see       search.xml
 *
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductSearch
{
    /**
     * @var MDB2_Driver_Common
     */
    protected $db;

    /**
     * @var SwatUI
     */
    protected $ui;

    protected $order_by_clause;
    protected $join_clause;
    protected $where_clause;

    /**
     * Performs a search on products.
     *
     * @param SwatUI             $ui the user interface contgaining search parameters
     * @param MDB2_Driver_Common $db the database to search
     *
     * @see StoreProductSearch::getJoinClause()
     * @see StoreProductSearch::getWhereClause()
     * @see StoreProductSearch::getOrderByClause()
     */
    public function __construct(SwatUI $ui, MDB2_Driver_Common $db)
    {
        $this->ui = $ui;
        $this->db = $db;

        $this->buildJoinClause();
        $this->buildWhereClause();
        $this->buildOrderByClause();
    }

    public function getOrderByClause()
    {
        return $this->order_by_clause;
    }

    public function getJoinClause()
    {
        return $this->join_clause;
    }

    public function getWhereClause()
    {
        return $this->where_clause;
    }

    /**
     * Gets the search type for products for this web-application.
     *
     * @return string the search type for products for this web-application or
     *                null if fulltext searching is not implemented for the
     *                current application
     */
    protected function getSearchType()
    {
        return 'product';
    }

    /**
     * Builds the SQL join clause for a product search.
     *
     * @see StoreProductSearch::getJoinClause()
     */
    protected function buildJoinClause()
    {
        $this->join_clause = '';
    }

    /**
     * Builds the SQL where clause for a product search.
     *
     * @see StoreProductSearch::getWhereClause()
     */
    protected function buildWhereClause()
    {
        $where = '1 = 1';

        $where .= $this->buildKeywordsWhereClause();
        $where .= $this->buildSkuWhereClause();
        $where .= $this->buildCategoryWhereClause();
        $where .= $this->buildCatalogWhereClause();
        $where .= $this->buildSaleDiscountWhereClause();
        $where .= $this->buildMinimumQuantityGroupWhereClause();
        $where .= $this->buildItemStatusWhereClause();

        $this->where_clause = $where;
    }

    protected function buildKeywordsWhereClause()
    {
        $where = '';
        // keywords are included in the where clause if fulltext searching is
        // turned off
        $keywords = $this->ui->getWidget('search_keywords')->value;
        if (trim($keywords) != '') {
            $where .= ' and (';

            $clause = new AdminSearchClause('title');
            $clause->table = 'Product';
            $clause->value = $keywords;
            $clause->operator = AdminSearchClause::OP_CONTAINS;
            $where .= $clause->getClause($this->db, '');

            $clause = new AdminSearchClause('bodytext');
            $clause->table = 'Product';
            $clause->value = $keywords;
            $clause->operator = AdminSearchClause::OP_CONTAINS;
            $where .= $clause->getClause($this->db, 'or');

            $where .= ') ';
        }

        return $where;
    }

    protected function buildSkuWhereClause()
    {
        $where = '';
        $clause = new AdminSearchClause('sku');
        $clause->table = 'ItemView';
        $clause->value = $this->ui->getWidget('search_item')->value;
        $clause->operator = $this->ui->getWidget('search_item_op')->value;
        $item_where = $clause->getClause($this->db, '');
        if ($item_where != '') {
            $where .= sprintf(' and Product.id in (
				select ItemView.product from ItemView where %s)', $item_where);
        }

        // multiple items
        $value = $this->ui->getWidget('search_items')->value;
        $items = [];
        if ($value != '') {
            $items = preg_split('/[ ,\s]/u', $value, -1, PREG_SPLIT_NO_EMPTY);
            if (count($items) > 0) {
                $where .= sprintf(
                    ' and Product.id in (
					select ItemView.product from ItemView
					where ItemView.sku in (%s))',
                    $this->db->implodeArray($items, 'text')
                );
            }
        }

        return $where;
    }

    protected function buildCategoryWhereClause()
    {
        $where = '';
        $category = $this->ui->getWidget('search_category')->value;

        if ($category == -1) {
            $where .= ' and Product.id not in (
				select product from CategoryProductBinding)';
        } else {
            $clause = new AdminSearchClause('integer:category');
            $clause->value = $category;
            $clause->table = 'category_descendants';
            $category_descendant_where = $clause->getClause($this->db, '');

            if ($category_descendant_where != '') {
                $where .= sprintf(
                    ' and Product.id in (select product from
					CategoryProductBinding
					inner join getCategoryDescendants(%s) as
						category_descendants
						on category_descendants.descendant =
							CategoryProductBinding.category
					where %s)',
                    $this->db->quote($category, 'integer'),
                    $category_descendant_where
                );
            }
        }

        return $where;
    }

    protected function buildCatalogWhereClause()
    {
        $where = '';
        $catalog_selector = $this->ui->getWidget('catalog_selector');

        $where .= sprintf(
            ' and Product.catalog in (%s)',
            $catalog_selector->getSubQuery()
        );

        return $where;
    }

    protected function buildSaleDiscountWhereClause()
    {
        $where = '';
        $sale_discount = $this->ui->getWidget('search_sale_discount')->value;

        if ($sale_discount !== null) {
            $where .= sprintf(
                ' and Product.id in
				(select product from Item where sale_discount = %s)',
                $this->db->quote($sale_discount)
            );
        }

        return $where;
    }

    protected function buildMinimumQuantityGroupWhereClause()
    {
        $where = '';
        $item_minimum_quantity_group = $this->ui->getWidget(
            'search_item_minimum_quantity_group'
        )->value;

        if ($item_minimum_quantity_group !== null) {
            $where .= sprintf(
                ' and Product.id in (select product from Item ' .
                'where minimum_quantity_group = %s)',
                $this->db->quote($item_minimum_quantity_group)
            );
        }

        return $where;
    }

    protected function buildItemStatusWhereClause()
    {
        $where = '';
        $item_status = $this->ui->getWidget('search_item_status')->value;

        if ($item_status !== null) {
            $where .= sprintf(
                ' and Product.id in (select product from Item ' .
                'where status = %s)',
                $this->db->quote($item_status)
            );
        }

        return $where;
    }

    /**
     * Builds the SQL join clause for a product search.
     *
     * @see StoreProductSearch::getOrderByClause()
     */
    protected function buildOrderByClause()
    {
        $this->order_by_clause = 'Product.title, Product.id';
    }
}
