<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';
require_once 'XML/RPCAjax.php';
require_once 'Store/Store.php';

/**
 * @package   Store
 * @copyright 2009-2012 silverorange
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
	}

	// }}}
	// {{{ public function display()

	public function display(SwatDisplayContext $context)
	{
		if (!$this->visible) {
			return;
		}

		SwatWidget::display($context);

		$div = new SwatHtmlTag('div');
		$div->id = $this->id;
		$div->class = $this->getCSSClassString();

		$div->open($context);
		$this->displayContent($context);
		$div->close($context);

		$ajax = new XML_RPCAjax();

		$context->addYUI('yahoo', 'dom', 'event');
		$context->addScript($ajax->getHtmlHeadEntrySet());
		$context->addScript(
			'packages/store/javascript/store-feedback-panel.js'
		);
		$context->addStyleSheet(
			'packages/store/styles/store-feedback-panel.css'
		);
		$context->addInlineScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent(SwatDisplayContext $context)
	{
		$link = new SwatHtmlTag('a');
		$link->id = $this->id.'_link';
		$link->class = 'store-feedback-panel-title';
		$link->href = $this->link;
		$link->setContent($this->title);
		$link->display($context);
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
