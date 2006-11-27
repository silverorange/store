<?php

require_once 'NateGoSearch/NateGoSearchQuery.php'; 

/**
 * Performs a NateGoSearch on products
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreProductNateGoSearch
{
	// {{{ private properties

	private $db;
	private $order_by_clause;
	private $join_clause;

	// }}}
	// {{{ public function __construct()

	/**
	 * Performs a fulltext search on products using NateGoSearch
	 *
	 * @param MDB2_Driver_Common $db the database containing the NateGoSearch
	 *                                index.
	 * @param string $keywords the keywords to search for.
	 *
	 * @see StoreProductNateGoSearch::getOrderByClause()
	 * @see StoreProductNateGoSearch::getJoinClause()
	 */
	public function __construct(MDB2_Driver_Common $db, $keywords)
	{
		if ($keywords !== null) {

			$query = new NateGoSearchQuery($db);
			$query->addDocumentType($this->getProductSearchType());
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
	// {{{ protected abstract function getProductSearchType()

	/**
	 * Gets the search type for products for this web-application
	 *
	 * @return integer the search type for products for this web-application.
	 */
	protected abstract function getProductSearchType();

	// }}}
}

?>
