<?php

require_once 'Admin/AdminMenuView.php';

require_once 'Swat/SwatForm.php';
require_once 'Swat/SwatFrame.php';
require_once 'Swat/SwatFormField.php';
require_once 'Swat/SwatEntry.php';
require_once 'Swat/SwatButton.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * A veseys specific admin menu view
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class VeseysAdminMenuView extends AdminMenuView
{
	// {{{ protected properties

	protected $form;
	protected $sku_entry;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Veseys admin menu view
	 *
	 * The Veseys admin has a sku quick search displayed at the top of the
	 * menu.
	 *
	 * @param AdminMenuStore $store the menu store to view.
	 */
	public function __construct(AdminMenuStore $store, $id = null)
	{
		parent::__construct($store, $id);

		$this->store = $store;

		$entry = new SwatEntry('quick_search_sku');
		$entry->size = 5;
		$this->sku_entry = $entry;

		$button = new SwatButton();
		$button->stock_id = 'submit';
		$button->title = 'Go';

		$field = new SwatFormField();
		$field->title = 'SKU';
		$field->add($entry);
		$field->add($button);

		$form = new SwatForm('quick_search_form');
		$form->action = 'Product';
		$form->add($field);

		$this->form = $form;

		$this->html_head_entry_set->addEntry(
			new SwatStyleSheetHtmlHeadEntry('styles/quick-sku-search.css'));
	}

	// }}}
	// {{{public function getForm()

	public function getForm()
	{
		return $this->form;
	}

	// }}}
	// {{{ public function getSkuEntry()

	public function getSkuEntry()
	{
		return $this->sku_entry;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this menu
	 *
	 * The Veseys menu displays a quick SKU search form on top of the menu.
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
		$this->displayJavaScript();

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
