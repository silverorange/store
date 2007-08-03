<?php

require_once 'Site/SitePath.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
class StoreCategoryPath extends SitePath
{
	// {{{ protected function loadFromId()

	/**
	 * Creates a new path object
	 *
	 * @param integer $category_id.
	 */
	public function loadFromId(SiteWebApplication $app, $category_id)
	{
		foreach ($this->queryPath($app, $category_id) as $row)
			$this->addEntry(new SitePathEntry(
				$row->id, $row->parent, $row->shortname, $row->title));
	}

	// }}}
	// {{{ protected function queryPath()

	protected function queryPath($app, $category_id)
	{
		$sql = sprintf('select * from getCategoryPathInfo(%s)',
			$app->db->quote($category_id, 'integer'));

		return SwatDB::query($app->db, $sql);
	}

	// }}}
}

?>
