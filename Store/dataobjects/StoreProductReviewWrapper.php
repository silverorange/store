<?php

/**
 * A recordset wrapper class for ProductReview objects
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 */
class StoreProductReviewWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('StoreProductReview');
		$this->index_field = 'id';
	}

	// }}}
}

?>
