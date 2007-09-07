<?php

require_once 'Site/admin/components/Article/include/SiteArticleVisibilityCellRenderer.php';

/**
 * Cell renderer that displays a summary of the visibility of an article
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticleVisibilityCellRenderer extends SiteArticleVisibilityCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		$messages = array();

		if (!$this->searchable)
			$messages[] = Site::_('not searchable');
		elseif ($this->display_positive_states)
			$messages[] = Site::_('searchable');

		if (!$this->show_in_menu)
			$messages[] = Site::_('not shown in menu');
		elseif ($this->display_positive_states)
			$messages[] = Site::_('shown in menu');

		echo implode($this->separator, $messages);
	}

	// }}}
}

?>
