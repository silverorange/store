<?php

require_once 'Swat/SwatForm.php';

/**
 * Form that submits feedback asynchronously via JavaScript
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackForm extends SwatForm
{
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$yui = new SwatYUI(array('yahoo', 'dom', 'event', 'connection'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/store/javascript/store-feedback-form.js',
			Store::PACKAGE_ID
		);
	}

	// }}}
	// {{{ protected function getJavaScriptClass()

	protected function getJavaScriptClass()
	{
		return 'StoreFeedbackForm';
	}

	// }}}
}

?>
