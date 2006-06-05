<?php

require_once 'Swat/SwatNavBar.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreArticleWrapper.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
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
	 * ID of parent category
	 *
	 * @var integer
	 */
	public $parent;

	/**
	 *
	 *
	 * @var varchar(255)
	 */
	public $shortname;

	/**
	 * User visible title
	 *
	 * @var varchar(255)
	 */
	public $title;

	/**
	 *
	 *
	 * @var text
	 */
	public $bodytext;

	/**
	 *
	 *
	 * @var timestamp
	 */
	public $createdate;

	/**
	 *
	 *
	 * @var int not null default 0
	 */
	public $displayorder;

	// }}}
	// {{{ public function getNavbar()

	public function getNavbar($link, $use_path = false)
	{
		$sql = sprintf('select * from getCategoryNavbar(%s)',
			$this->db->quote($this->id, 'integer'));

		$rs = SwatDB::query($this->db, $sql);
		$navbar = new SwatNavBar();
		$path = '';

		foreach ($rs as $row) {
			$path.=$row->shortname;
			$link_value = $use_path ? $path : $row->id;
			$navbar->createEntry($row->title, sprintf($link, $link_value));
			$path.='/';
		}

		return $navbar;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalField('path');
		$this->registerDateField('createdate');

		$this->table = 'Category';
		$this->id_field = 'integer:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadPath()

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
}

?>
