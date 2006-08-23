<?php

require_once 'Swat/SwatForm.php';

/**
 * Custom form with overridden displayHiddenFields() method to not display
 * any hidden fields
 *
 * This is useful if the form's method is set to HTTP GET.
 *
 * @package   Store 
 * @copyright 2006 silverorange
 * @see SwatForm::setMethod()
 */
class StoreSearchForm extends SwatForm
{
	// {{{ public function __construct()

	public function __construct()
	{
		$this->setMethod(SwatForm::METHOD_GET);
	}

	// }}}
	// {{{ protected function displayHiddenFields()

	protected function displayHiddenFields()
	{
		// override to not output any hidden fields
	}

	// }}}
}

?>
