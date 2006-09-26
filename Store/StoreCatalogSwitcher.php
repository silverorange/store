<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatButton.php';
require_once 'Swat/SwatControl.php';

require_once 'CatalogSelector.php';

/**
 * A widget to switch the active catalog(s) in the admin
 *
 * The active catalog(s) is used for category pages.
 *
 * @package   veseys2
 * @copyright 2005-2006 silverorange
 */
class CatalogSwitcher extends SwatControl
{
	public $db;

	private $catalog_selector;
	private $switch_button;

	/**
	 * Creates a new catalog selector widget
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet('styles/catalog-switcher.css');
	}

	public function init()
	{
		$this->catalog_selector = new CatalogSelector($this->id.'_selector');
		$this->catalog_selector->parent = $this;
		$this->catalog_selector->db = $this->db;
		$this->catalog_selector->init();

		$state = SiteApplication::initVar('catalog', null, 
			SiteApplication::VAR_SESSION);

		if ($state === null) {
			$this->catalog_selector->scope =
				CatalogSelector::ALL_ENABLED_CATALOGS;
		} else {
			$valid_state = true;
			$state_exp = explode('_', $state);
			$scope = $state_exp[0];
			$value = (count($state_exp) == 2) ? $state_exp[1] : null;
			switch ($scope) {
			// make sure it is a valid catalogue
			case CatalogSelector::ONE_CATALOG:
				$sql = sprintf('select count(id) from Catalog where id = %s',
					$this->db->quote($value, 'integer'));

				if (SwatDB::queryOne($this->db, $sql) == 0) {
					$valid_state = false;

					$this->catalog_selector->region = null;
					$this->catalog_selector->catalog= null;
					$this->catalog_selector->scope =
						CatalogSelector::ALL_ENABLED_CATALOGS;

					unset($_SESSION['catalog']);
				}
				break;

			// make sure it is a valid region
			case CatalogSelector::ALL_ENABLED_CATALOGS_IN_REGION:
				$sql = sprintf('select count(id) from Region where id = %s',
					$this->db->quote($value, 'integer'));

				if (SwatDB::queryOne($this->db, $sql) == 0) {
					$valid_state = false;

					$this->catalog_selector->region = null;
					$this->catalog_selector->catalog= null;
					$this->catalog_selector->scope =
						CatalogSelector::ALL_ENABLED_CATALOGS;

					unset($_SESSION['catalog']);
				}
				break;
			}
			if ($valid_state)
				$this->catalog_selector->setState($state);
		}

		$this->switch_button = new SwatButton($this->id.'_switch_button');
		$this->switch_button->parent = $this;
		$this->switch_button->title = 'Switch';
		$this->switch_button->init();
	}

	public function display()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'catalog-switcher';
		$div_tag->open();

		$label_tag = new SwatHtmlTag('label');
		$label_tag->for = $this->id.'_selector';
		$label_tag->setContent('Catalogue:');
		$label_tag->display();
		
		echo '&nbsp;';
		$this->catalog_selector->display();
		echo '&nbsp;';
		$this->switch_button->display();

		$div_tag->close();
	}

	public function process()
	{
		$this->switch_button->process();
		$this->catalog_selector->process();

		if ($this->switch_button->hasBeenClicked())
			$_SESSION['catalog'] = $this->catalog_selector->getState();
	}

	public function getSubQuery()
	{
		return $this->catalog_selector->getSubQuery();
	}
}

?>
