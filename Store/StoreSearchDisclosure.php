<?php

require_once 'Swat/exceptions/SwatException.php';
require_once 'Swat/SwatDisclosure.php';
require_once 'Swat/SwatSearchEntry.php';
require_once 'Site/SiteUnnamedButton.php';
require_once 'Site/SiteSearchForm.php';
require_once 'XML/RPCAjax.php';

/**
 * Special disclosure widget for displaying the search panel for
 *
 * @package   Store
 * @copyright 2007-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSearchDisclosure extends SwatDisclosure
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $keywords_id;

	/**
	 * @var string
	 */
	public $form_action;

	/**
	 * @var string
	 */
	public $button_title;

	/**
	 * @var string
	 */
	public $input_text;

	/**
	 * Panel height in {@link StoreSearchDisclosure::$panel_unit} units
	 *
	 * @var float
	 */
	public $panel_height = 13;

	/**
	 * Panel height units
	 *
	 * Should be one of 'em', 'px', 'pt', '%', etc...
	 *
	 * @var string
	 */
	public $panel_units = 'em';

	/**
	 * Access key used by the entry control within this disclosure's header
	 *
	 * @var string
	 */
	public $access_key;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$ajax = new XML_RPCAjax();
		$this->html_head_entry_set->addEntrySet($ajax->getHtmlHeadEntrySet());
		$this->addJavaScript('packages/store/javascript/search-disclosure.js',
			Store::PACKAGE_ID);

		$this->addStyleSheet('packages/store/styles/search-disclosure.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$form = $this->getFirstAncestor('SwatForm');
		if ($form !== null) {
			throw new SwatException('StoreSeachDisclosure '.
				'can not be contained in a form.');
		}
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		SwatWidget::display();

		$form = $this->getCompositeWidget('form');
		$form->action = $this->form_action;

		$control_div = $this->getControlDivTag();
		$span = $this->getSpanTag();
		$input = $this->getInputTag();
		$container_div = $this->getContainerDivTag();
		$animate_div = $this->getAnimateDivTag();
		$animate_div->id = 'search-disclosure-animate';
		$search_controls_div = new SwatHtmlTag('div');
		$search_controls_div->class = 'search-disclosure-search-controls';
		$search_controls_div->id = $this->id.'_search_controls';
		$header_div = new SwatHtmlTag('div');
		$header_div->class = 'search-disclosure-header no-js';

		$control_div->open();

		$header_div->open();

		$search_controls_div->open();
		$form->display();
		$input->display();
		$search_controls_div->close();

		$span->display();

		echo '<div style="clear:both;"></div>';

		$header_div->close();

		$container_div->open();
		$animate_div->open();

		$sub_container_div = new SwatHtmlTag('div');
		$sub_container_div->class = 'search-disclosure-sub-container';
		$sub_container_div->id = $this->id.'_sub_container';
		$sub_container_div->open();

		if ($this->open)
			$this->displayChildren();
		else
			$this->displayLoadingContainer();

		$sub_container_div->close();
		$animate_div->close();
		$container_div->close();

		Swat::displayInlineJavaScript($this->getInlineJavascript());

		$control_div->close();
	}

	// }}}
	// {{{ protected function getSpanTag()

	protected function getSpanTag()
	{
		$span = new SwatHtmlTag('span');
		$span->id = $this->id.'_span';
		$span->setContent('');
		return $span;
	}

	// }}}
	// {{{ protected function getContainerDivTag()

	protected function getContainerDivTag()
	{
		$tag = parent::getContainerDivTag();
		$tag->id = $this->id.'_container';
		return $tag;
	}

	// }}}
	// {{{ protected function getJavaScriptClass()

	protected function getJavaScriptClass()
	{
		return 'StoreSearchDisclosure';
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets disclosure specific inline JavaScript
	 *
	 * @return string disclosure specific inline JavaScript.
	 */
	protected function getInlineJavaScript()
	{
		$open        = ($this->open) ? 'true' : 'false';
		$title       = SwatString::quoteJavaScriptString($this->title);
		$keywords_id = SwatString::quoteJavaScriptString($this->keywords_id);
		$panel_units = SwatString::quoteJavaScriptString($this->panel_units);

		$options = array(
			'title'        => $title,
			'panel_height' => $this->panel_height,
			'panel_units'  => $panel_units,
			'keywords_id'  => $keywords_id,
		);

		$options_string = "{\n";
		foreach ($options as $name => $value) {
			$options_string.= "\t".SwatString::quoteJavaScriptString($name).
				': '.$value.",\n";
		}
		$options_string = substr($options_string, 0, -2);
		$options_string.= "\n}";

		return sprintf(
			"var %s_obj = new %s('%s', %s, %s_entry_obj, %s);",
			$this->id,
			$this->getJavaScriptClass(),
			$this->id,
			$open,
			$this->id,
			$options_string
		);
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	protected function getCSSClassNames()
	{
		$class_names = parent::getCSSClassNames();
		$class_names[] = 'search-disclosure';

		// SearchDisclosure displays open or closed by default. This is the
		// opposite behaviour of SwatDisclosure which is accessible w/o
		// JavaScript.
		if (!$this->open) {
			$class_names[] = 'swat-disclosure-control-closed';
			$class_names = array_diff($class_names,
				array('swat-disclosure-control-opened'));
		}

		return $class_names;
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$button = new SiteUnnamedButton();
		$button->title = ($this->button_title === null) ?
			Store::_('Search') : $this->button_title;

		$entry = new SwatSearchEntry();
		$entry->id = $this->id.'_entry';
		$entry->name = 'keywords';
		$entry->size = 20;
		$entry->access_key = $this->access_key;
		$entry->maxlength = 255;

		$entry_field = new SwatFormField();
		$entry_field->id = $this->id.'_keywords_field';
		$entry_field->title = ($this->input_text === null) ?
			Store::_('Keywords or Item #') : $this->input_text;

		$entry_field->add($entry);
		$entry_field->add($button);

		$form = new SiteSearchForm();
		$form->id = $this->id.'_form';
		$form->add($entry_field);

		$this->addCompositeWidget($form, 'form');
	}

	// }}}
	// {{{ private function displayLoadingContainer()

	private function displayLoadingContainer()
	{
		$loading_div_tag = new SwatHtmlTag('div');
		$loading_div_tag->id = $this->id.'_loading_container';
		$loading_div_tag->class = 'search-disclosure-loading';
		$loading_div_tag->setContent('');
		$loading_div_tag->display();
	}

	// }}}
}

?>
