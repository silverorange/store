<?php

require_once 'Swat/SwatTableViewGroup.php';
require_once 'Swat/SwatImageCellRenderer.php';
require_once 'Swat/SwatLinkCellRenderer.php';

/**
 * @package   Store
 * @copyright 2004-2008 silverorange
 */
class StoreCartImageTableViewGroup extends SwatTableViewGroup
{
	// {{{ protected function displayGroupHeader()

	/**
	 * Displays the group header for this grouping column
	 *
	 * The grouping header is displayed at the beginning of a group.
	 *
	 * @param mixed $row a data object containing the data for the first row in
	 *                    in the table store for this group.
	 */
	protected function displayGroupHeader($row)
	{
		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->open();

		$td_tag = new SwatHtmlTag('td');
		$td_tag->rowspan = $row->item_count + 1;
		$td_tag->open();

		$this->getFirstDescendant('SwatImageCellRenderer')->render();

		$td_tag->close();

		$td_tag = new SwatHtmlTag('td', $this->getTdAttributes());
		$td_tag->colspan = $this->view->getXhtmlColspan() - 1;
		$td_tag->open();
		$this->displayRenderersInternal($row);
		$td_tag->close();

		$tr_tag->close();
	}

	// }}}
	// {{{ protected function displayRenderersInternal()

	protected function displayRenderersInternal($data)
	{
		$this->getFirstDescendant('SwatLinkCellRenderer')->render();
	}

	// }}}
}

?>
