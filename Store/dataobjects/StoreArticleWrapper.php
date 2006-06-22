<?php

require_once 'StoreRecordsetWrapper.php';
require_once 'StoreArticle.php';

/**
 * A recordset wrapper class for StoreArticle objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreArticle
 */
class StoreArticleWrapper extends StoreRecordsetWrapper
{
	// {{{ public function getByShortname()

	/**
	 * Gets a single article from this recordset by the article's shortname
	 *
	 * If two or more articles in the recordset have the same shortname, the
	 * first one is returned.
	 *
	 * @param string $shortname the shortname of the article to get from this
	 *                           recordset.
	 */
	public function getByShortname($shortname)
	{
		$returned_article = null;

		foreach($this as $article) {
			if ($article->shortname === $shortname) {
				$returned_article = $article;
				break;
			}
		}

		return $returned_article;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreArticle');
	}

	// }}}
}

?>
