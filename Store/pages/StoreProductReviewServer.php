<?php

//require_once 'Store/StoreProductReviewView.php';

/**
 * Handles XML-RPC requests to view more product reviews
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
 */
class StoreProductReviewServer extends SiteXMLRPCServer
{
	// {{{ public function getReviews()

	/**
	 * Returns the XHTML and JavaScript required to display a reviews for a
	 * product
	 *
	 * @param integer $product_id the id of the product for which to get
	 *                             reviews.
	 * @param integer $limit limit the number of returned reviews to this
	 *                        number. If zero is specified, all reviews are
	 *                        returned.
	 * @param integer $offset offset return reviews at the specified offset.
	 *
	 * @return array an array of reviews. Each review is a three element array
	 *                containing the markup and inline JavaScript for the
	 *                product reviews. The array contains elements named
	 *                'id', 'content' and 'javascript'.
	 */
	public function getReviews($product_id, $limit, $offset)
	{
		$limit  = intval($limit);
		$limit  = ($limit === 0) ? null : $limit;
		$offset = intval($offset);

		// get views
		$reviews  = $this->getProductReviews($product_id, $limit, $offset);
		$view     = $this->getProductReviewView();
		$response = array();

		foreach ($reviews as $review) {
			$response[] = $this->getResponseReview($view, $review);
			foreach ($review->replies as $reply) {
				$response[] = $this->getResponseReview($view, $reply);
			}
		}

		return $response;
	}

	// }}}
	// {{{ protected function getResponseReview()

	/**
	 * @xmlrpc.hidden
	 */
	protected function getResponseReview(StoreProductReviewView $view,
		StoreProductReview $review)
	{
		$response_review = array();

		// display id
		$response_review['id'] = $view->getId($review);

		// display content
		ob_start();
		$view->display($review); //todo, don't display js inline
		$response_review['content'] = ob_get_clean();

		// display javascript
		ob_start();
		echo $view->getInlineJavaScript($review);
		echo "\n";
		$response_review['javascript'] = ob_get_clean();

		return $response_review;
	}

	// }}}
	// {{{ protected function getProductReviewView()

	/**
	 * @xmlrpc.hidden
	 */
	protected function getProductReviewView()
	{
		$view = SiteViewFactory::get($this->app, 'product-review');
		$view->setPartMode('replies',    SiteView::MODE_NONE);
		$view->setPartMode('javascript', SiteView::MODE_NONE);

		return $view;
	}

	// }}}
	// {{{ protected function getProductReviews()

	/**
	 * @xmlrpc.hidden
	 */
	protected function getProductReviews($product_id, $limit = null,
		$offset = 0)
	{
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select * from ProductReview
			where product = %s and spam = %s and status = %s and instance %s %s
				and parent %s %s
			order by createdate desc, id',
			$this->app->db->quote($product_id, 'integer'),
			$this->app->db->quote(false, 'boolean'),
			$this->app->db->quote(SiteComment::STATUS_PUBLISHED, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			SwatDB::equalityOperator(null),
			$this->app->db->quote(null));

		// Don't use db->setLimit here because it doesn't allow an offset
		// with no limit.

		if ($limit !== null) {
			$sql.= sprintf(' limit %s',
				$this->app->db->quote($limit, 'integer'));
		}

		if ($offset > 0) {
			$sql.= sprintf(' offset %s',
				$this->app->db->quote($offset, 'integer'));
		}

		$reviews = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreProductReviewWrapper'));

		// efficiently load replies
		$replies = $reviews->loadAllSubRecordsets(
			'replies',
			SwatDBClassMap::get('StoreProductReviewWrapper'),
			'ProductReview',
			'parent',
			'',
			'createdate asc'
			);

		return $reviews;
	}

	// }}}
}

?>
