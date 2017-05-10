<?php


/**
 * @package   Store
 * @copyright 2015-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSalesByRegionGroup extends SwatTableViewGroup
{
	// {{{ protected function displayGroupHeader()

	protected function displayGroupHeader($row)
	{
		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->class = 'swat-table-view-group';
		$tr_tag->open();

		$td_tag = new SwatHtmlTag('td', $this->getTdAttributes());
		$td_tag->colspan = $this->view->getXhtmlColspan();
		$td_tag->open();
		$this->renderers->getFirst()->render();
		$td_tag->close();

		$tr_tag->close();
	}

	// }}}
	// {{{ protected function displayGroupFooter()

	protected function displayGroupFooter($row)
	{
		$visible_renderer_count = $this->getVisibleRendererCount();
		if ($visible_renderer_count === 0) {
			return;
		}

		$tr_tag = new SwatHtmlTag('tr');
		$tr_tag->class = 'swat-table-view-group';
		$tr_tag->open();

		// First renderer in this group is displayes in the header. All other
		// renderers are displayed in table cells in the footer. The first
		// cell in the footer (second renderer) has a colspan so the columns
		// of the footer equal the columns of the table-view.
		$first = true;
		$second = true;
		foreach ($this->renderers as $renderer) {
			if ($first) {
				$first = false;
				continue;
			}

			if (!$renderer->isVisible()) {
				continue;
			}

			$td_tag = new SwatHtmlTag('td', $this->getTdAttributes());
			$td_tag->class = implode(
				' ',
				$this->getFooterCSSClassNames($renderer)
			);

			if ($second) {
				$td_tag->colspan = $this->view->getXhtmlColspan() -
					$visible_renderer_count + 1;

				$second = false;
			}

			$td_tag->open();
			$renderer->render();
			$td_tag->close();
		}

		$tr_tag->close();
	}

	// }}}
	// {{{ protected function getFooterCSSClassNames()

	protected function getFooterCSSClassNames(SwatCellRenderer $renderer = null)
	{
		$classes = array();

		// instance specific class
		if ($this->id !== null && !$this->has_auto_id) {
			$column_class = str_replace('_', '-', $this->id);
			$classes[] = $column_class;
		}

		// base classes
		$classes = array_merge($classes, $this->getBaseCSSClassNames());

		// user-specified classes
		$classes = array_merge($classes, $this->classes);

		if ($this->show_renderer_classes &&
			$renderer instanceof SwatCellRenderer) {

			// renderer inheritance classes
			$classes = array_merge(
				$classes,
				$renderer->getInheritanceCSSClassNames()
			);

			// renderer base classes
			$classes = array_merge(
				$classes,
				$renderer->getBaseCSSClassNames()
			);

			// renderer data specific classes
			if ($this->renderers->mappingsApplied()) {
				$classes = array_merge(
					$classes,
					$renderer->getDataSpecificCSSClassNames()
				);
			}

			// renderer user-specified classes
			$classes = array_merge($classes, $renderer->classes);
		}

		return $classes;
	}

	// }}}
	// {{{ protected function getVisibleRendererCount()

	protected function getVisibleRendererCount()
	{
		$first = true;
		$count = 0;
		foreach ($this->renderers as $renderer) {
			if ($first) {
				$first = false;
				continue;
			}
			if ($renderer->isVisible()) {
				$count++;
			}
		}
		return $count;
	}

	// }}}
}

?>
