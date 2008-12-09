<?php

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/SiteApplication.php';
require_once 'Store/dataobjects/StoreProductReview.php';

/**
 * @package   Store
 * @copyright 2008 silverorange
 */
class StoreProductReviewView extends SwatControl
{
	// {{{ public properties

	public $review;

	public $app;

	// }}}
	// {{{ protected properties

	protected $description = null;
	protected $summary = null;
	protected $summarized = false;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = true;

		$yui = new SwatYUI(array('dom', 'event', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/store/javascript/store-product-review-view.js',
			Store::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/store/styles/store-product-review-view.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if (!($this->app instanceof SiteApplication)) {
			throw new SwatException('Application is not set for this view.'.
				'Set $view->app to an instance of SiteApplication.');
		}

		if (!($this->review instanceof StoreProductReview)) {
			throw new SwatException('Review is not set for this view.'.
				'Set $view->review to an instance of StoreProductReview.');
		}

		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'product-review hreview';
		$div_tag->open();

		$this->displayHeader();
		$this->displayItem();
		$this->displayDescription();
		$this->displaySummary();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'reviewer vcard';
		$div_tag->open();

		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'product-review-title';

		$heading_tag->open();
		$this->displayAuthor($this->review);
		$this->displayDate($this->review);
		$heading_tag->close();

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'product-review-author fn';
		if ($this->review->author_review)
			$span_tag->class.= ' system-product-review-author';

		if (class_exists('Blorg') && $this->review->author != null)
			$fullname = $this->review->author->name;
		else
			$fullname = $this->review->fullname;

		$span_tag->setContent($fullname);
		$span_tag->display();
	}

	// }}}
	// {{{ protected function displayDate()

	protected function displayDate()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'product-review-date';
		$span_tag->open();

		// display machine-readable date in UTC
		$abbr_tag = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'dtreviewed';
		$abbr_tag->title =
			$this->review->createdate->getDate(DATE_FORMAT_ISO_EXTENDED);

		// display human-readable date in local time
		$date = clone $this->review->createdate;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent($date->format(SwatDate::DF_DATE));
		$abbr_tag->display();

		$span_tag->close();
	}

	// }}}
	// {{{ protected function displayItem()

	protected function displayItem()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'product-review-item item fn';
		$div_tag->open();

		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'type';
		$span_tag->setContent('product');

		echo SwatString::minimizeEntities($this->review->product->title);

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayDescription()

	protected function displayDescription()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'product-review-description description';
		$div_tag->setContent($this->getDescription(), 'text/xml');
		$div_tag->display();
	}

	// }}}
	// {{{ protected function displaySummary()

	protected function displaySummary()
	{
		$summary = $this->getSummary();
		if ($summary !== false) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'product-review-summary summary';
			$div_tag->setContent($summary, 'text/xml');
			$div_tag->display();
		}
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
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
			$this->id,
			SwatString::quoteJavaScriptString($this->id));

		return $javascript;
	}

	// }}}
	// {{{ protected function getDescription()

	protected function getDescription()
	{
		if ($this->description === null &&
			$this->review instanceof StoreProductReview) {
			$this->description =
				SiteCommentFilter::toXhtml($this->review->bodytext);
		}

		return $this->description;
	}

	// }}}
	// {{{ protected function getSummary()

	protected function getSummary()
	{
		if ($this->summary === null &&
			$this->review instanceof StoreProductReview) {

			$this->summarized = false;
			$summary = SwatString::ellipsizeRight(
				$this->review->bodytext, 300, ' … ', $this->summarized);

			if ($this->summarized) {
				$this->summary = SiteCommentFilter::toXhtml($summary);
			} else {
				$this->summary = false;
			}
		}

		return $this->summary;
	}

	// }}}
}

?>
