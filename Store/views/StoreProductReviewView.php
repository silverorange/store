<?php

require_once 'Swat/Swat.php';
require_once 'Swat/SwatString.php';
require_once 'Site/views/SiteView.php';
require_once 'Site/SiteCommentFilter.php';
require_once 'Store/dataobjects/StoreProductReview.php';

/**
 * Reviews are displayed using the hReview microformat.
 *
 * @package   Store
 * @copyright 2008-2011 silverorange
 */

class StoreProductReviewView extends SiteView
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $bodytext_summary_length = 200;

	/**
	 * @var StoreProductReviewView
	 */
	protected $reply_view = null;

	// }}}
	// {{{ public function setBodytextSummaryLength()

	public function setBodytextSummaryLength($length)
	{
		$this->bodytext_summary_length = intval($length);
	}

	// }}}
	// {{{ public function setReplyView()

	public function setReplyView(StoreProductReviewView $view)
	{
		$this->reply_view = $view;
	}

	// }}}
	// {{{ public function getId()

	public function getId(StoreProductReview $review)
	{
		return 'review'.$review->id;
	}

	// }}}
	// {{{ public function display()

	/**
	 * @param StoreProductReview $review
	 */
	public function display($review)
	{
		if (!($review instanceof StoreProductReview)) {
			throw new InvalidArgumentException(
				'The $review parameter must be an StoreProductReview object.');
		}

		$id = $this->getId($review);

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $id;
		$div_tag->class = 'product-review hreview';

		if ($review->author_review) {
			$div_tag->class.= ' system-product-review';
		}

		if ($review->parent !== null) {
			$div_tag->class.= ' product-review-reply';
		}

		$div_tag->open();

		$this->displayHeader($review);
		$this->displayItem($review);
		$this->displayDescription($review);
		$this->displaySummary($review);

		$mode = $this->getMode('javascript');
		if ($mode > SiteView::MODE_NONE) {
			Swat::displayInlineJavaScript($this->getInlineJavaScript($review));
		}

		$div_tag->close();
		$this->displayReplies($review);
	}

	// }}}
	// {{{ public function getInlineJavaScript()

	public function getInlineJavaScript(StoreProductReview $review)
	{
		static $translations_displayed = false;

		$javascript = '';

		if (!$translations_displayed) {
			$javascript.= sprintf(
				"StoreProductReviewView.open_text = %s;\n",
					SwatString::quoteJavaScriptString(
					Store::_('read full comment')));

			$javascript.= sprintf(
				"StoreProductReviewView.close_text = %s;\n",
					SwatString::quoteJavaScriptString(
					Store::_('show less')));

			$translations_displayed = true;
		}

		$javascript.= sprintf(
			"var %s_obj = new StoreProductReviewView(%s);",
			$this->getId($review),
			SwatString::quoteJavaScriptString($this->getId($review)));

		return $javascript;
	}

	// }}}
	// {{{ public function getRepliesInlineJavaScript()

	public function getRepliesInlineJavaScript(StoreProductReview $review)
	{
		$javascript = '';
		$view = $this->getRepliesView();
		foreach ($review->replies as $reply) {
			$javascript.= $view->getInlineJavaScript($reply)."\n";
		}

		return $javascript;
	}

	// }}}

	// {{{ protected function define()

	protected function define()
	{
		$this->definePart('replies');
		$this->definePart('javascript');
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader(StoreProductReview $review)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'reviewer vcard';
		$div_tag->open();

		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'product-review-title';

		$heading_tag->open();
		$this->displayAuthor($review);
		$this->displayDate($review);
		$heading_tag->close();

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor(StoreProductReview $review)
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'product-review-author fn';

		if (class_exists('Blorg') && $review->author != null) {
			$fullname = $review->author->name;
		} else {
			$fullname = $review->fullname;
		}

		$span_tag->setContent($fullname);
		$span_tag->display();
	}

	// }}}
	// {{{ protected function displayDate()

	protected function displayDate(StoreProductReview $review)
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'product-review-date';
		$span_tag->open();

		// display machine-readable date in UTC
		$abbr_tag = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'dtreviewed';
		$abbr_tag->title = $review->createdate->getISO8601();

		// display human-readable date in local time
		$date = clone $review->createdate;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent($date->formatLikeIntl(SwatDate::DF_DATE));
		$abbr_tag->display();

		$span_tag->close();
	}

	// }}}
	// {{{ protected function displayItem()

	protected function displayItem(StoreProductReview $review)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'product-review-item item fn';
		$div_tag->open();

		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'type';
		$span_tag->setContent('product');

		echo SwatString::minimizeEntities($review->product->title);

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayDescription()

	protected function displayDescription(StoreProductReview $review)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'product-review-description description';
		$div_tag->setContent($this->getDescription($review), 'text/xml');
		$div_tag->display();
	}

	// }}}
	// {{{ protected function displaySummary()

	protected function displaySummary(StoreProductReview $review)
	{
		$summary = $this->getSummary($review);
		if ($summary !== false) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'product-review-summary summary';
			$div_tag->setContent($summary, 'text/xml');
			$div_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayReplies()

	protected function displayReplies(StoreProductReview $review)
	{
		$mode = $this->getMode('replies');
		if ($mode > SiteView::MODE_NONE) {
			if (count($review->replies)) {
				$view = $this->getRepliesView();
				foreach ($review->replies as $reply) {
					$view->display($reply);
				}
			}
		}
	}

	// }}}
	// {{{ protected function getRepliesView()

	protected function getRepliesView()
	{
		if (!($this->reply_view instanceof StoreProductReviewView)) {
			$this->reply_view = clone $this;
		}

		return $this->reply_view;
	}

	// }}}
	// {{{ protected function getDescription()

	protected function getDescription(StoreProductReview $review)
	{
		return SiteCommentFilter::toXhtml($review->bodytext, true);
	}

	// }}}
	// {{{ protected function getSummary()

	protected function getSummary(StoreProductReview $review)
	{
		$summarized = false;

		$summary = SwatString::ellipsizeRight(
			$review->bodytext,
			$this->bodytext_summary_length,
			' … ',
			$summarized);

		if ($summarized) {
			$summary = SiteCommentFilter::toXhtml($summary, true);
		} else {
			$summary = false;
		}

		return $summary;
	}

	// }}}
}

?>
