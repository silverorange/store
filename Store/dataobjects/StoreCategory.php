<?php

require_once 'Swat/SwatNavBarEntry.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreArticleWrapper.php';

/**
 * A category for an e-commerce web application
 *
 * Categories are a navigational network that lies beneath the products and
 * items of a store. The sole purpose of categories is to organize products
 * and items into meaningful and navigatable sets.
 *
 * <pre>
 * Category
 * |
 * -- Product
 *    |
 *    -- Item
 * </pre>
 *
 * One category may belong to another category and may contain multiple
 * categories. There is no restriction on placing a single category into
 * multiple categories so categories do not represent a tree structure.
 * 
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategory extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Identifier of parent category
	 *
	 * If this category is a root category, this property is null.
	 *
	 * @var integer
	 */
	public $parent;

	/**
	 * Short, textual identifier of this category
	 *
	 * This identifier is designed to be used in URL's.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible title
	 *
	 * @var string 
	 */
	public $title;

	/**
	 * Body text of this category
	 *
	 * This text is intended to be displayed on a page dedicated to this
	 * category.
	 *
	 * @var string 
	 */
	public $bodytext;

	/**
	 * The date this category was created
	 *
	 * @var Date 
	 */
	public $createdate;

	/**
	 * Order of display of this category
	 *
	 * @var integer
	 */
	public $displayorder;

	// }}}
	// {{{ protected properties

	protected $join_region = null;

	// }}}
	// {{{ public function setRegion()

	public function setRegion($region)
	{
		$this->join_region = $region;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('path');
		$this->registerInternalProperty('product_count');
		$this->registerDateProperty('createdate');

		$this->table = 'Category';
		$this->id_field = 'integer:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadPath()

	/**
	 * Loads the URL fragment of this category
	 *
	 * If the path was part of the initial query to load this category, that
	 * value is returned. Otherwise, a separate query gets the path of this
	 * category. If you are calling this method frequently during a single
	 * request, it is more efficient to include the path in the initial
	 * category query.
	 */
	protected function loadPath()
	{
		$path = '';

		if ($this->hasInternalValue('path') &&
			$this->getInternalValue('path') !== null) {
			$path = $this->getInternalValue('path');
		} else {
			$sql = sprintf('select getCategoryPath(%s)',
				$this->db->quote($this->id, 'integer'));

			$path = SwatDB::queryOne($this->db, $sql);
		}

		return $path;
	}

	// }}}
	// {{{ protected function loadProductCount()

	/**
	 * Loads the count of visible products in this category
	 *
	 * If the product_count was part of the initial query to load this
	 * category, that value is returned. Otherwise, a separate query gets the
	 * product_count of this category. If you are calling this method
	 * frequently during a singlerequest, it is more efficient to include the
	 * product_count in the initial category query.
	 */
	protected function loadProductCount()
	{
		$product_count = '';

		if ($this->hasInternalValue('product_count') &&
			$this->getInternalValue('product_count') !== null) {
			$product_count = $this->getInternalValue('product_count');
		} else {
			$sql = 'select product_count
				from CategoryVisibleProductCountByRegion
				where region = %s and category = %s';

			$sql = sprintf($sql,
				$this->db->quote($this->join_region, 'integer'),
				$this->db->quote($this->id, 'integer'));

			$product_count = SwatDB::queryOne($this->db, $sql);
		}

		return $product_count;
	}

	// }}}
	// {{{ protected function loadRelatedArticles()

	/**
	 * Loads related articles
	 *
	 * Related articles are ordered by the article table's display order.
	 *
	 * @see StoreArticle::loadRelatedCategories()
	 */
	protected function loadRelatedArticles()
	{
		$sql = 'select Article.*, getArticlePath(id) as path
			from Article 
				inner join ArticleCategoryBinding
					on Article.id = ArticleCategoryBinding.article
						and ArticleCategoryBinding.category = %s
			order by Article.displayorder asc';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		$wrapper = $this->class_map->resolveClass('StoreArticleWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function loadNavBarEntries()

	/**
	 * Loads a set of {@link SwatNavbarEntry} objects for this category 
	 *
	 * The links in the navbar entries are intended for the customer visible
	 * side of an e-commerce application.
	 */
	protected function loadNavBarEntries()
	{
		$entries = array();

		$path = '';
		foreach ($this->queryNavBar() as $row) {
			if (strlen($path) == 0)
				$path.= $row->shortname;
			else
				$path.= '/'.$row->shortname;

			$entries[] = new SwatNavBarEntry($row->title, $path);
		}

		return $entries;
	}

	// }}}
	// {{{ protected function loadAdminNavBarEntries()

	/**
	 * Loads a set of {@link SwatNavbarEntry} objects for this category
	 *
	 * The links in the navbar entries are intended for the administration side
	 * of an e-commerce application.
	 */
	protected function loadAdminNavBarEntries()
	{
		$entries = array();

		foreach ($this->queryNavBar() as $row) {
			$link = sprintf('Category/Index?id=%s', $row->id);
			$entries[] = new SwatNavBarEntry($row->title, $link);
		}

		return $entries;
	}

	// }}}
	// {{{ private function queryNavBar()

	/**
	 * Helper method for loading navbar entries of this category
	 */
	protected function queryNavBar()
	{
		$sql = sprintf('select * from getCategoryNavbar(%s)',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql);
	}

	// }}}
}

?>
