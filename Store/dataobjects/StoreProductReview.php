<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 * Product review for a product
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 */
class StoreProductReview extends SiteComment
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('product',
			SwatDBClassMap::get('StoreProduct'));

		$this->registerInternalProperty('parent',
			SwatDBClassMap::get('StoreProductReview'));

		$this->table = 'ProductReview';
	}

	// }}}

	// display methods
	// {{{ public function display()

	public function display(StoreApplication $app)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'review'.$this->id;
		$div_tag->class = 'review';

		$div_tag->open();
		$this->displayHeader($app);
		$this->displayBodytext($app);
		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader(StoreApplication $app)
	{
		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'review-title';

		$heading_tag->open();
		$this->displayAuthor($app);
		$this->displayDate($app);
		$heading_tag->close();

		$this->displayLink();
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor(StoreApplication $app)
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'comment-author';
		$span_tag->setContent($this->fullname);
		$span_tag->display();
	}

	// }}}
	// {{{ protected function displayDate()

	protected function displayDate(StoreApplication $app)
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'review-date';
		$span_tag->open();

		// display machine-readable date in UTC
		$abbr_tag = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'comment-published';
		$abbr_tag->title =
			$this->createdate->getDate(DATE_FORMAT_ISO_EXTENDED);

		// display human-readable date in local time
		$date = clone $this->createdate;
		$date->convertTZ($app->default_time_zone);
		$abbr_tag->setContent($date->format(SwatDate::DF_DATE_TIME));
		$abbr_tag->display();

		$span_tag->close();
	}

	// }}}
	// {{{ protected function displayLink()

	protected function displayLink()
	{
		if ($this->link != '') {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'review-link';
			$div_tag->open();

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $this->link;
			$anchor_tag->class = 'review-link';
			$anchor_tag->setContent($this->link);
			$anchor_tag->display();

			$div_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayBodytext()

	protected function displayBodytext()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'review-content';
		$div_tag->setContent(
			SiteCommentFilter::toXhtml($this->bodytext), 'text/xml');

		$div_tag->display();
	}

	// }}}
}

?>
