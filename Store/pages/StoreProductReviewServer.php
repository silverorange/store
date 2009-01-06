<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Store/StoreProductReviewView.php';
require_once 'Store/dataobjects/StoreProductReview.php';
require_once 'Store/dataobjects/StoreProductReviewWrapper.php';

/**
 * Handles XML-RPC requests to view more product reviews
 *
 * @package   Store
 * @copyright 2008-2009 silverorange
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

		$response = array();

		// get views
		$reviews = $this->getProductReviews($product_id, $limit, $offset);
		foreach ($reviews as $review) {
			$view = $this->getProductReviewView($review);

			$response_review = array();

			// display id
			$response_review['id'] = $view->id;

			// display content
			ob_start();
			$view->display();
			$response_review['content'] = ob_get_clean();

			// display javascript
			ob_start();
			echo $view->getInlineJavaScript();
			echo "\n";
			$response_review['javascript'] = ob_get_clean();

			// add review
			$response[] = $response_review;
		}

		return $response;
	}

	// }}}
	// {{{ protected function getProductReviewView()

	/**
	 * @xmlrpc.hidden
	 */
	protected function getProductReviewView(StoreProductReview $review)
	{
		$view = new StoreProductReviewView('review_'.$review->id);

		$view->app             = $this->app;
		$view->review          = $review;
		$view->show_javascript = false;

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
			order by createdate, id',
			$this->app->db->quote($product_id, 'integer'),
			$this->app->db->quote(false, 'boolean'),
			$this->app->db->quote(SiteComment::STATUS_PUBLISHED, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

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

		return SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreProductReviewWrapper'));
	}

	// }}}
}

?>
