<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Store/dataobjects/StoreProduct.php';
if (class_exists('Blorg'))
	require_once 'Blorg/dataobjects/BlorgAuthor.php';

/**
 * Product review for a product
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
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
			$this->registerInternalProperty('author',
				SwatDBClassMap::get('BlorgAuthor'));
		}

		$this->table = 'ProductReview';
	}

	// }}}
}

?>
