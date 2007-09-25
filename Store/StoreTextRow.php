<?php

require_once 'Swat/SwatTableViewRow.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * A an extra row for displaying textual content
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
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

	public function display()
	{
		if (!$this->visible)
			return;

		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->id = $this->id;

		$tr_tag->open();
		$this->displayHeader();
		$this->displayContent();
		$this->displayBlank();
		$tr_tag->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$colspan = $this->view->getXhtmlColspan();
		$th_tag = new SwatHtmlTag('th');
		$th_tag->colspan = $colspan - 1 - $this->offset;
		$th_tag->open();

		if ($this->link === null) {
			echo SwatString::minimizeEntities($this->title);
		} else {
			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $this->link;
			$anchor_tag->title = $this->link_title;
			$anchor_tag->setContent($this->title);
			$anchor_tag->display();
		}
		echo ':';

		if ($this->note !== null) {
			$div = new SwatHtmlTag('div');
			$div->class = 'note';
			$div->setContent($this->note, $this->note_content_type);
			$div->display();
		}

		$th_tag->close();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$td_tag = new SwatHtmlTag('td');
		$td_tag->class = $this->getCSSClassString();
		$td_tag->open();

		if ($this->content_type === 'text/plain')
			echo SwatString::minimizeEntities($this->text);
		else
			echo $this->text;

		$td_tag->close();
	}

	// }}}
	// {{{ protected function displayBlank()

	protected function displayBlank()
	{
		if ($this->offset > 0) {
			$td_tag = new SwatHtmlTag('td');
			$td_tag->colspan = $this->offset;
			$td_tag->setContent('&nbsp;');
			$td_tag->display();
		}
	}

	// }}}
}

?>
