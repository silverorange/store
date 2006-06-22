<?php

require_once 'Swat/SwatNavBarEntry.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';

/**
 * An article in an e-commerce web application
 *
 * Articles on an e-commerce web application represent navigatable pages that
 * are outside the category/product structure hierarchy. Examples include
 * an "about us" page, a newsletter signup page and a shipping policy page.
 *
 * StoreArticle objects themselves may represent a tree structure by accessing
 * the {@link StoreAddress::$parent} property. 
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticle extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * User visible description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * User visible content
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Create date
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * Whether article can be loaded on the front-end (customer visible)
	 *
	 * @var boolean
	 */
	public $enabled;

	/**
	 * Whether article is listed in sub-article lists
	 *
	 * @var boolean
	 */
	public $show;

	/**
	 * Weather article is included in search results
	 *
	 * @var boolean
	 */
	public $searchable;

	/**
	 * Short, textual identifer for this article
	 *
	 * The shortname must be unique among siblings and is intended for use
	 * in URL's.
	 *
	 * @var string
	 */
	public $shortname;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('parent',
			$this->class_map->resolveClass('StoreArticle'));

		$this->registerDateProperty('createdate');

		$this->table = 'Article';
		$this->id_field = 'integer:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadPath()

	/**
	 * Loads the URL fragment of this article
	 *
	 * If the path was part of the initial query to load this article, that
	 * value is returned. Otherwise, a separate query gets the path of this
	 * article. If you are calling this method frequently during a single
	 * request, it is more efficient to include the path in the initial
	 * article query.
	 */
	protected function loadPath()
	{
		$path = '';

		if ($this->hasInternalValue('path') &&
			$this->getInternameValue('path') !== null) {
			$path = $this->getInternalValue('path');
		} else {
			$sql = sprintf('select getArticlePath(%s)',
				$this->db->quote($this->id, 'integer'));

			$path = SwatDB::queryOne($this->db, $sql);
		}

		return $path;
	}

	// }}}
	// {{{ protected function loadRelatedCategories()

	/**
	 * Loads related cateogries 
	 *
	 * Related categories are ordered by the category table's display order.
	 *
	 * @see StoreCategory::loadRelatedArticles()
	 */
	protected function loadRelatedCategories()
	{
		$sql = 'select Category.*, getCategoryPath(id) as path
			from Category 
				inner join ArticleCategoryBinding
					on Category.id = ArticleCategoryBinding.category
						and ArticleCategoryBinding.article = %s
			order by Category.displayorder asc';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		$wrapper = $this->class_map->resolveClass('StoreCategoryWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function loadNavBarEntries()

	/**
	 * Loads a set of {@link SwatNavbarEntry} objects for this article
	 */
	protected function loadNavBarEntries()
	{
		$sql = sprintf('select * from getArticleNavbar(%s)',
			$this->db->quote($this->id, 'integer'));

		$navbar_rows = SwatDB::query($this->db, $sql);
		$entries = array();

		$path = '';
		foreach ($navbar_rows as $row) {
			if (strlen($path) == 0)
				$path.= $row->shortname;
			else
				$path.= '/'.$row->shortname;

			$entries[] = new SwatNavBarEntry($row->title,	$path);
		}

		return $entries;
	}

	// }}}
}

?>
