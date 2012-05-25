<?php

require_once 'Swat/SwatTableViewRow.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * A an extra row for displaying textual content
 *
 * @package   Store
 * @copyright 2005-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreTextRow extends SwatTableViewRow
{
	// {{{ public properties

	/**
	 * Text to display
	 *
	 * @var string
	 */
	public $text;

	/**
	 * Optional content type for the text
	 *
	 * Defaults to text/plain, use text/xml for XHTML fragments.
	 *
	 * @var string
	 */
	public $content_type = 'text/plain';

	public $title = null;
	public $link = null;
	public $link_title = null;
	public $offset = 0;
	public $note = null;
	public $note_content_type = 'text/plain';

	// }}}
	// {{{ public function display()

	public function display(SwatDisplayContext $context)
	{
		if (!$this->visible) {
			return;
		}

		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->id = $this->id;

		$tr_tag->open($context);
		$this->displayHeader($context);
		$this->displayContent($context);
		$this->displayBlank($context);
		$tr_tag->close($context);
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader(SwatDisplayContext $context)
	{
		$colspan = $this->view->getXhtmlColspan();
		$th_tag = new SwatHtmlTag('th');
		$th_tag->colspan = $colspan - 1 - $this->offset;
		$th_tag->open($context);

		if ($this->link === null) {
			$context->out(SwatString::minimizeEntities($this->title));
		} else {
			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $this->link;
			$anchor_tag->title = $this->link_title;
			$anchor_tag->setContent($this->title);
			$anchor_tag->display($context);
		}

		$context->out(':');

		if ($this->note !== null) {
			$div = new SwatHtmlTag('div');
			$div->class = 'note';
			$div->setContent($this->note, $this->note_content_type);
			$div->display($context);
		}

		$th_tag->close($context);
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent(SwatDisplayContext $context)
	{
		$td_tag = new SwatHtmlTag('td');
		$td_tag->class = $this->getCSSClassString();
		$td_tag->open($context);

		if ($this->content_type === 'text/plain') {
			$context->out(SwatString::minimizeEntities($this->text));
		} else {
			$context->out($this->text);
		}

		$td_tag->close($context);
	}

	// }}}
	// {{{ protected function displayBlank()

	protected function displayBlank(SwatDisplayContext $context)
	{
		if ($this->offset > 0) {
			$td_tag = new SwatHtmlTag('td');
			$td_tag->colspan = $this->offset;
			$td_tag->setContent('&nbsp;');
			$td_tag->display($context);
		}
	}

	// }}}
}

?>
