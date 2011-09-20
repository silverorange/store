<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 * Product review for a product
 *
 * @package   Store
 * @copyright 2006-2011 silverorange
 */
class StoreProductReview extends SiteComment
{
	// {{{ public properties

	/**
	 * Whether or not this review was posted by an author on the site
	 *
	 * @var boolean
	 */
	public $author_review = false;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('product',
			SwatDBClassMap::get('StoreProduct'));

		$this->registerInternalProperty('parent',
			SwatDBClassMap::get('StoreProductReview'));

		if (class_exists('Blorg')) {
			require_once 'Blorg/dataobjects/BlorgAuthor.php';
			$this->registerInternalProperty('author',
				SwatDBClassMap::get('BlorgAuthor'));
		}

		$this->table = 'ProductReview';
	}

	// }}}

	// loader methods
	// {{{ protected function loadReplies()

	protected function loadReplies()
	{
		// include wrapper at call-time to prevent infinite include loop
		require_once dirname(__FILE__).'/StoreProductReviewWrapper.php';

		// order chronologically for sub-items
		$sql = sprintf('select * from ProductReview where parent = %s
			order by createdate asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductReviewWrapper'));
	}

	// }}}
}

?>
