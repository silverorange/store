<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';
require_once 'XML/RPCAjax.php';
require_once 'Store/Store.php';

/**
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackPanel extends SwatControl
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $link = 'feedback';

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->title = Store::_(
			'Can’t find what you’re looking for? Let us know!'
		);

		$this->requires_id = true;

		$yui = new SwatYUI(array('yahoo', 'dom', 'event'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$ajax = new XML_RPCAjax();
		$this->html_head_entry_set->addEntrySet($ajax->getHtmlHeadEntrySet());

		$this->addStyleSheet(
			'packages/store/styles/store-feedback-panel.css',
			Store::PACKAGE_ID
		);

		$this->addJavaScript(
			'packages/store/javascript/store-feedback-panel.js',
			Store::PACKAGE_ID
		);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		SwatWidget::display();

		$div = new SwatHtmlTag('div');
		$div->id = $this->id;
		$div->class = $this->getCSSClassString();

		$div->open();
		$this->displayContent();
		$div->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$link = new SwatHtmlTag('a');
		$link->id = $this->id.'_link';
		$link->class = 'store-feedback-panel-title';
		$link->href = $this->link;
		$link->setContent($this->title);
		echo $link;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		return sprintf(
			'%s_obj = new StoreFeedbackPanel(%s);',
			$this->id,
			SwatString::quoteJavaScriptString($this->id)
		);
	}

	// }}}
}

?>
