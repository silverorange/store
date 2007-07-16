<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatString.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Store/pages/StorePage.php';
require_once 'Store/dataobjects/StoreArticle.php';
require_once 'Store/dataobjects/StoreArticleWrapper.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * A page for loading and displaying articles
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreArticle
 */
class StoreArticlePage extends StorePage 
{
	// {{{ public properties

	public $article_id;

	// }}}
	// {{{ protected properties

	/**
	 * @var StoreArticle
	 */
	protected $article;

	// }}}
	// {{{ public function hasParentInPath()

	/**
	 * Whether or not this page has the parent id in its path
	 *
	 * @param integer $article_id the parent article id to check.
	 *
	 * @return boolean true if this page has the given id in its path and false
	 *                  if it does not.
	 */
	public function hasParentInPath($article_id)
	{
		return $this->path->hasId($article_id);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initArticle();
		$this->layout->selected_article_id = $this->article->id;
	}

	// }}}
	// {{{ public function isVisibleInRegion()

	public function isVisibleInRegion(StoreRegion $region)
	{
		$article = null;

		if ($this->path !== null) {
			$path_entry = $this->path->getLast();
			if ($path_entry !== null) {
				$article_id = $path_entry->id;

				$sql = sprintf('select id from EnabledArticleView
					where id = %s and region = %s',
					$this->app->db->quote($article_id, 'integer'),
					$this->app->db->quote($region->id, 'integer'));

				$article = SwatDB::queryOne($this->app->db, $sql);
			}
		}

		return ($article !== null);
	}

	// }}}
	// {{{ protected function initArticle()

	protected function initArticle()
	{
		// don't try to resolve articles that are deeper than the max depth
		if (count($this->path) > StoreArticle::MAX_DEPTH)
			throw new SiteNotFoundException(
				sprintf('Article page not found for path ‘%s’', $this->path));

		if (($this->article = $this->queryArticle($this->article_id)) === null)
			throw new SiteNotFoundException(
				sprintf('Article dataobject failed to load for article id ‘%s’',
				$this->article_id));
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
		$sub_articles = $this->querySubArticles($this->article->id);
		$this->layout->data->title =
			SwatString::minimizeEntities((string)$this->article->title);

		$this->layout->data->meta_description =
			SwatString::minimizeEntities(SwatString::condense(
			SwatString::stripXHTMLTags($this->article->bodytext, 400)));

		$this->layout->startCapture('content');
		$this->displayArticle($this->article);
		$this->displaySubArticles($sub_articles);
		$this->layout->endCapture();

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->path !== null) {
			$navbar = $this->layout->navbar;
			$link = '';
			$first = true;
			foreach ($this->path as $path_entry) {
				if ($first) {
					$link.= $path_entry->shortname;
					$first = false;
				} else {
					$link.= '/'.$path_entry->shortname;
				}

				$navbar->createEntry($path_entry->title, $link);
			}
		}
	}

	// }}}
	// {{{ protected function queryArticle()

	/**
	 * Gets an article object from the database
	 *
	 * @param integer $id the database identifier of the article to get.
	 *
	 * @return StoreArticle the specified article or null if no such article
	 *                       exists.
	 */
	protected function queryArticle($article_id)
	{
		$sql = 'select * from Article where id = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($article_id, 'integer'));

		$wrapper = SwatDBClassMap::get('StoreArticleWrapper');
		$articles = SwatDB::query($this->app->db, $sql, $wrapper);
		return $articles->getFirst();
	}

	// }}}
	// {{{ protected function displayArticle()

	/**
	 * Displays an article
	 *
	 * @param StoreArticle $article the article to display.
	 */
	protected function displayArticle(StoreArticle $article)
	{
		if (strlen($article->bodytext) > 0) {
			$bodytext = (string)$article->bodytext;
			$bodytext = $this->replaceMarkers($bodytext);
			echo '<div id="article_bodytext">', $bodytext, '</div>';
		}
	}

	// }}}
	// {{{ protected function displaySubArticles()

	/**
	 * Displays a set of articles as sub-articles
	 *
	 * @param StoreArticleWrapper $articles the set of articles to display.
	 * @param string $path an optional string containing the path to the
	 *                      article being displayed.
	 *
	 * @see StoreArticlePage::displaySubArticle()
	 */
	protected function displaySubArticles(StoreArticleWrapper $articles,
		$path = null)
	{
		if (count($articles) == 0)
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

	/**
	 * Displays an article as a sub-article
	 *
	 * @param StoreArticle $article the article to display.
	 * @param string $path an optional string containing the path to the
	 *                      article being displayed. If no path is provided,
	 *                      the path of the current page is used.
	 */
	protected function displaySubArticle(StoreArticle $article, $path = null)
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

	/**
	 * Gets sub-articles of an article
	 *
	 * @param integer $id the database identifier of the article from which to
	 *                     get sub-articles.
	 *
	 * @return StoreArticleWrapper a recordset of sub-articles of the
	 *                              specified article.
	 */
	protected function querySubArticles($article_id)
	{
		$sql = 'select id, title, shortname, description from Article
			where parent %s %s';

		if ($this->app instanceof StoreLocaleApplication)
			$sql.= sprintf(
				' and id in (select id from VisibleArticleView where region = %s)',
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$sql.= ' order by displayorder, title';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($article_id),
			$this->app->db->quote($article_id, 'integer'));

		$wrapper = SwatDBClassMap::get('StoreArticleWrapper');
		return SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function getReplacementMarkerText()

	/**
	 * Gets replacement text for a specfied replacement marker identifier
	 *
	 * @param string $marker_id the id of the marker found in the article
	 *                           bodytext.
	 *
	 * @return string the replacement text for the given marker id.
	 */
	protected function getReplacementMarkerText($marker_id)
	{
		// by default, always return a blank string as replacement text
		return '';
	}

	// }}}
	// {{{ protected final function replaceMarkers()

	/**
	 * Replaces markers in article with dynamic content
	 *
	 * @param string $text the bodytext of the article.
	 *
	 * @return string the article bodytext with markers replaced by dynamic
	 *                 content.
	 *
	 * @see StoreArticlePage::getReplacementMarkerText()
	 */
	protected final function replaceMarkers($text)
	{
		$marker_pattern = '/<!-- \[(.*)?\] -->/u';
		$callback = array($this, 'getReplacementMarkerTextByMatches');
		return preg_replace_callback($marker_pattern, $callback, $text);
	}

	// }}}
	// {{{ private final function getReplacementMarkerTextByMatches()

	/**
	 * Gets replacement text for a replacement marker from within a matches
	 * array returned from a PERL regular expression
	 *
	 * @param array $matches the PERL regular expression matches array.
	 *
	 * @return string the replacement text for the first parenthesized
	 *                 subpattern of the <i>$matches</i> array.
	 */
	private final function getReplacementMarkerTextByMatches($matches)
	{
		if (isset($matches[1]))
			return $this->getReplacementMarkerText($matches[1]);

		return '';
	}

	// }}}
}

?>
