<?php

require_once 'Admin/pages/AdminApproval.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Store/dataobjects/StoreProductReview.php';

/**
 * Approval page for Product reviews
 *
 * @package   Store
 * @copyright 2008-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class ProductReviewApproval extends AdminApproval
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$title = Store::_('Product Review Approval');
		$this->navbar->createEntry($title);
		$this->ui->getWidget('frame')->title = $title;
	}

	// }}}
	// {{{ protected function initDataObject()

	protected function initDataObject($id)
	{
		$class_name = SwatDBClassMap::get('StoreProductReview');
		$this->data_object = new $class_name();
		$this->data_object->setDatabase($this->app->db);

		if (!$this->data_object->load($id))
			throw new AdminNotFoundException(
				sprintf(Store::_('Review with id ‘%s’ not found.'), $id));
	}

	// }}}
	// {{{ protected function getPendingIds()

	protected function getPendingIds()
	{
		$sql = sprintf('select id from ProductReview
			where status = %s and spam = %s
			order by createdate asc',
			$this->app->db->quote(SiteComment::STATUS_PENDING, 'integer'),
			$this->app->db->quote(false, 'boolean'));

		$rows = SwatDB::query($this->app->db, $sql);

		$ids = array();
		foreach ($rows as $row)
			$ids[] = $row->id;

		return $ids;
	}

	// }}}

	// process phase
	// {{{ protected function approve()

	protected function approve()
	{
		$this->data_object->status = SiteComment::STATUS_PUBLISHED;
		$this->data_object->save();
	}

	// }}}

	// build phase
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$review = $this->data_object;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->setContent($this->data_object->product->title);
		$div_tag->display();

		$h2_tag = new SwatHtmlTag('h2');
		$h2_tag->setContent($this->data_object->fullname);
		$h2_tag->display();

		$abbr_tag = new SwatHtmlTag('abbr');
		$date = clone $review->createdate;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent(sprintf(Store::_('Posted: %s'),
			$date->format(SwatDate::DF_DATE)));

		$abbr_tag->display();

		echo SwatString::toXHTML($review->bodytext);
	}

	// }}}
}

?>
