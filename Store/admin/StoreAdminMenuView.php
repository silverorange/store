<?php

require_once 'Swat/SwatForm.php';
require_once 'Swat/SwatFrame.php';
require_once 'Swat/SwatFormField.php';
require_once 'Swat/SwatEntry.php';
require_once 'Swat/SwatButton.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Admin/AdminMenuView.php';

/**
 * An admin menu view that has an item search box at the top
 *
 * @package   Store
 * @copyright 2006-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdminMenuView extends AdminMenuView
{
	// {{{ protected properties

	/**
	 * @var SwatForm
	 */
	protected $form;

	/**
	 * @var SwatEntry
	 */
	protected $item_entry;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new store admin menu view with an item search box at the top
	 *
	 * @param string $id the unique identifier of this menu-view.
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->html_head_entry_set->addEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/admin/styles/store-quick-search.css',
			Store::PACKAGE_ID));
	}

	// }}}
	// {{{public function getForm()

	/**
	 * Gets the form containing the item search box of this menu
	 *
	 * @return SwatForm
	 */
	public function getForm()
	{
		return $this->getCompositeWidget('form');
	}

	// }}}
	// {{{ public function getItemEntry()

	/**
	 * Gets the item search box of this menu
	 *
	 * @return SwatEntry
	 */
	public function getItemEntry()
	{
		$this->confirmCompositeWidgets();
		return $this->item_entry;
	}

	// }}}
	// {{{ protected function displayMenuContent()

	/**
	 * Displays this admin menu content
	 *
	 * The store admin menu displays an item search form on top of the menu.
	 */
	protected function displayMenuContent()
	{
		// only show if we have access to products
		if ($this->store->getComponentByName('Product') !== null) {
			$form = $this->getCompositeWidget('form');
			$form->display();
		}

		parent::displayMenuContent();
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		parent::createCompositeWidgets();

		$entry = new SwatEntry('quick_search_item');
		$entry->placeholder = Store::_('Item #');
		$entry->access_key = '4';
		$this->item_entry = $entry;

		$button = new SwatButton();
		$button->stock_id = 'submit';
		$button->title = Store::_('Go');

		$field = new SwatFormField('quick_search_item_field');
		$field->add($entry);
		$field->add($button);

		$form = new SwatForm('quick_search_form');
		$form->action = 'Product';
		$form->add($field);

		$this->form = $form;

		$this->addCompositeWidget($this->form, 'form');
	}

	// }}}
}

?>
