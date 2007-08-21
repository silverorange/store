<?php

require_once 'Site/admin/components/Article/include/SiteArticleActionsProcessor.php';

/**
 * Processes actions on articles
 *
 * This class is used on both the article search results and on the articles
 * tree.
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreArticleActionsProcessor extends SiteArticleActionsProcessor
{
	// {{{ public function process()

	/**
	 * Processes actions on articles
	 *
	 * @param SwatTableView $view the view to process.
	 * @param SwatActions $actions the list of actions.
	 */
	public function process($view, $actions)
	{
		parent::process($view, $actions);

		$num = count($view->checked_items);
		$message = null;

		switch ($actions->selected->id) {
		case 'accessibility_action':
			$processor =
				$actions->selected->widget->getChild('article_region_action');

			$processor->setItems($view->checked_items);
			$processor->processAction();

			$message = new SwatMessage(sprintf(Store::ngettext(
				'Accessibility has been updated for one article.',
				'Accessibility has been updated for %d articles.', $num),
				SwatString::numberFormat($num)));

			break;
		}

		if ($message !== null)
			$this->page->app->messages->add($message);
	}

	// }}}
}

?>
