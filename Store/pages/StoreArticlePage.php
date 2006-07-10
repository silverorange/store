<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Store/pages/StorePage.php';
require_once 'Store/dataobjects/StoreArticleWrapper.php';
require_once 'Store/StoreClassMap.php';

/**
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreArticlePage extends StorePage
{
	// {{{ protected properties

	protected $path = null;

	// }}}
	// {{{ public function setSource()

	public function setSource($source)
	{
		$this->source = $source;

		if ($this->path === null)
			$this->path = $source;
	}

	// }}}
	// {{{ public function setPath()

	public function setPath($path)
	{
		$this->path = $path;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();
		$this->buildArticle();
	}

	// }}}
	// {{{ protected function buildArticle()

	protected function buildArticle()
	{
		if (($article_id = $this->findArticle()) === null)
			throw new SiteNotFoundException(
				sprintf("Article page not found for path '%s'", $this->path));

		if (($article = $this->queryArticle($article_id)) === null)
			throw new SiteNotFoundException(
				sprintf("Article dataobject failed to load for article id %s",
				$article_id));

		$sub_articles = $this->querySubArticles($article_id);
		$this->layout->data->title =
			SwatString::minimizeEntities((string)$article->title);

		$this->layout->startCapture('content');
		$this->displayArticle($article);
		$this->displaySubArticles($sub_articles);
		$this->layout->endCapture();

		$this->layout->navbar->addEntries($article->navbar_entries);
	}

	// }}}
	// {{{ protected function findArticle()

	protected function findArticle()
	{
		// trim at 254 to prevent database errors
		$path = substr($this->path, 0, 254);
		$sql = sprintf('select findArticle(%s)',
			$this->app->db->quote($path, 'text'));

		$article_id = SwatDB::queryOne($this->app->db, $sql);

		return $article_id;
	}

	// }}}
	// {{{ protected function queryArticle()

	protected function queryArticle($article_id)
	{
		$sql = 'select * from Article where id = %s and enabled = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($article_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$articles = SwatDB::query($this->app->db, $sql, 'StoreArticleWrapper');

		return $articles->getFirst();
	}

	// }}}
	// {{{ protected function displayArticle()

	protected function displayArticle(StoreArticle $article)
	{
		if (strlen($article->bodytext)) {	
			echo '<div id="article-bodytext">',
			(string)$article->bodytext, '</div>';
		}
	}

	// }}}
	// {{{ protected function displaySubArticles()

	protected function displaySubArticles($articles, $path = null)
	{
		if ($articles->getCount() == 0)
			return;

		echo '<ul class="sub-articles">';

		foreach($articles as $article) {
			echo '<li>';
			$this->displaySubArticle($article, $path);
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function displaySubArticle()

	protected function displaySubArticle($article, $path = null)
	{
		if ($path === null)
			$path = $this->path;

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $path.'/'.$article->shortname;
		$anchor_tag->class = 'sub-article';
		$anchor_tag->setContent($article->title);
		$anchor_tag->display();

		if (strlen($article->description) > 0)
			echo ' - ', $article->description;
	}

	// }}}
	// {{{ protected function querySubArticles()

	protected function querySubArticles($article_id)
	{
		$sql = 'select id, title, shortname, description from Article where
			parent %s %s and enabled = %s and show = %s order by displayorder, title';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($article_id),
			$this->app->db->quote($article_id, 'integer'),
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(true, 'boolean'));

		$class_map = StoreClassMap::instance();
		$wrapper = $class_map->resolveClass('StoreArticleWrapper');
		return SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}
}

?>
