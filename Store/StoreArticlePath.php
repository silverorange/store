<?php

require_once 'Store/StorePath.php';
require_once 'Store/StorePathEntry.php';

/**
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreArticlePath extends StorePath
{
	// {{{ protected function loadFromId()

	/**
	 * Creates a new path object
	 *
	 * @param integer $article_id.
	 */
	public function loadFromId(StoreApplication $app, $article_id)
	{
		foreach ($this->queryPath($app, $article_id) as $row)
			$this->addEntry(new StorePathEntry(
				$row->id, $row->parent, $row->shortname, $row->title));
	}

	// }}}
	// {{{ protected function queryPath()

	protected function queryPath($app, $article_id)
	{
		$sql = sprintf('select * from getArticlePathInfo(%s)',
			$app->db->quote($article_id, 'integer'));

		return SwatDB::query($app->db, $sql);
	}

	// }}}
}

?>
