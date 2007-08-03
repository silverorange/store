<?php

require_once 'Admin/AdminMenuView.php';

require_once 'Swat/SwatForm.php';
require_once 'Swat/SwatFrame.php';
require_once 'Swat/SwatFormField.php';
require_once 'Swat/SwatEntry.php';
require_once 'Swat/SwatButton.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * An admin menu view that has an item search box at the top
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
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
	 * @param AdminMenuStore $store the menu store to view.
	 * @param string $id the unique identifier of this menu-view.
	 */
	public function __construct(AdminMenuStore $store, $id = null)
	{
		parent::__construct($store, $id);

		$this->store = $store;

		$entry = new SwatEntry('quick_search_item');
		$entry->size = 2;
		$this->item_entry = $entry;

		$button = new SwatButton();
		$button->stock_id = 'submit';
		$button->title = Store::_('Go');

		$field = new SwatFormField('quick_search_item_field');
		$field->title = Store::_('Item #');
		$field->access_key = '4';
		$field->add($entry);
		$field->add($button);

		$form = new SwatForm('quick_search_form');
		$form->action = 'Product';
		$form->add($field);

		$this->form = $form;

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
		return $this->form;
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
		return $this->item_entry;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this admin menu
	 *
	 * The store admin menu displays an item search form on top of the menu.
	 */
	public function display()
	{
		$this->displayShowLink();

		$menu_div = new SwatHtmlTag('div');
		$menu_div->id = $this->id;
		$menu_div->class = 'admin-menu';
		$menu_div->open();

		$this->displayHideLink();

		// only show if we have access to products
		if ($this->store->getComponentByName('Product') !== null) {
			$this->form->init();
			$this->form->display();
		}

		$this->displayMenuContent();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());

		$menu_div->close();
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();
		$set->addEntrySet($this->form->getHtmlHeadEntrySet());
		return $set;
	}

	// }}}
}

?>
