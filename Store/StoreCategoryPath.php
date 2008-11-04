<?php

require_once 'Site/SitePath.php';
require_once 'Store/StoreCategoryPathEntry.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
class StoreCategoryPath extends SitePath
{
	// {{{ public properties

	public static $twig_product_threshold = 60;
	public static $twig_category_threshold = 5;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new category path object
	 *
	 * @param SiteWebApplication $app the application this path exists in.
	 * @param integer $id the database id of the object to create the path for.
	 *                     If no database id is specified, an empty path is
	 *                     created.
	 */
	public function __construct(SiteWebApplication $app, $id = null)
	{
		if ($id !== null)
			$this->loadFromId($app, $id);
	}

	// }}}
	// {{{ protected function loadFromId()

	/**
	 * Creates a new path object
	 *
	 * @param integer $category_id.
	 */
	public function loadFromId(SiteWebApplication $app, $category_id)
	{
		foreach ($this->queryPath($app, $category_id) as $row)
			$this->addEntry(new StoreCategoryPathEntry(
				$row->id, $row->parent, $row->shortname, $row->title,
				$row->twig));
	}

	// }}}
	// {{{ protected function queryPath()

	protected function queryPath(StoreApplication $app, $category_id)
	{
		$sql = sprintf('select * from getCategoryPathInfo(%s, %s, %s)',
			$app->db->quote($category_id, 'integer'),
			$app->db->quote(self::$twig_product_threshold, 'integer'),
			$app->db->quote(self::$twig_category_threshold, 'integer'));

		return SwatDB::query($app->db, $sql);
	}

	// }}}
}

?>
