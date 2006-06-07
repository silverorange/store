<?php

require_once 'Swat/SwatNavBarEntry.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
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
	 * Whether article can be loaded on the front-end
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
	 * String identifer
	 *
	 * Unique among siblings and used in the URL.
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
	// {{{ protected function loadPath()

	protected function loadPath()
	{
		if ($this->hasInternalValue('path')) {
			$path = $this->getInternalValue('path');

			if ($path !== null)
				return $path;
		}

		$sql = sprintf('select getArticlePath(%s)',
			$this->db->quote($this->id, 'integer'));

		$path = SwatDB::queryOne($this->db, $sql);

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
