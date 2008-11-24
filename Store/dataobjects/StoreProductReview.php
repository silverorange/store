<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'ProductReviewImage.php';

/**
 * Product review for a product
 *
 * @package   Uppermost
 * @copyright 2006-2008 silverorange
 */
class StoreProductReview extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The unique identifier of this review
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The short description for the review
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The longer text of the review
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Email address of the reviewer
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Name of the reviewer
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * Review date
	 *
	 * @var date
	 */
	public $createdate;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('product', 'Product');
		$this->registerDateProperty('createdate');

		$this->table = 'ProductReview';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
