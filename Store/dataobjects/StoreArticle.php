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
	// {{{ class constants

	/**
	 * The maximum depth of articles in the article tree
	 *
	 * Objects that interact with articles may choose not to respect articles
	 * with a depth greater than this value.
	 *
	 * The root article is the zero-th level article. 
	 */
	MAX_DEPTH = 8;

	// }}}
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
	// {{{ public function loadWithPath()

	/**
	 * Loads an article from the database with a path in a region
	 *
	 * @param string $path the path of the article in the article tree. Article
	 *                      nodes are separated by a '/' character.
	 * @param StoreRegion $region the region to filter the article by.
	 * @param array $fields the article fields to load from the database. By
	 *                       default, only the id and title are loaded. The
	 *                       path pseudo-field is always populated from the
	 *                       <code>$path</code> parameter.
	 *
	 * @return boolean true if an article was successfully loaded and false if
	 *                  no article was found in the given region at the
	 *                  specified path.
	 */
	public function loadWithPath($path, StoreRegion $region,
		$fields = array('id', 'title'))
	{
		$this->checkDB();

		$found = false;

		$id_field = new SwatDBField($this->id_field, 'integer');
		foreach ($fields as &$field)
			$field = $this->table.'.'.$field;

		$sql = 'select %1$s from
				findArticle(%2$s)
			inner join %3$s on findArticle = %3$s.%4$s
			inner join VisibleArticleView on
				findArticle = VisibleArticleView.id and
					VisibleArticleView.region = %5$s';

		$sql = sprintf($sql,
			implode(', ', $fields),
			$this->db->quote($path, 'text'),
			$this->table,
			$id_field->name,
			$this->db->quote($region->id, 'integer'));

		$row = SwatDB::queryRow($this->db, $sql);
		if ($row !== null) {
			$this->initFromRow($row);
			$this->setInternalValue('path', $path);
			$this->generatePropertyHashes();
			$found = true;
		}

		return $found;
	}

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
