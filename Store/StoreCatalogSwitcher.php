<?php

/**
 * A widget to switch the active catalog(s) in the admin.
 *
 * The active catalog(s) is used for category pages.
 *
 * @copyright 2005-2016 silverorange
 */
class StoreCatalogSwitcher extends SwatControl
{
    /**
     * @var MDB2_Driver_Common
     */
    public $db;

    /**
     * Creates a new catalog selector widget.
     *
     * @param string $id a non-visible unique id for this widget
     *
     * @see SwatWidget::__construct()
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->addStyleSheet(
            'packages/store/admin/styles/store-catalog-switcher.css'
        );
    }

    public function init()
    {
        parent::init();

        $selector = $this->getCompositeWidget('selector');
        $state = SiteApplication::initVar(
            'catalog',
            null,
            SiteApplication::VAR_SESSION
        );

        if ($state === null) {
            $selector->scope = StoreCatalogSelector::ALL_ENABLED_CATALOGS;
        } else {
            $valid_state = true;
            $state_exp = explode('_', $state);
            $scope = $state_exp[0];
            $value = (count($state_exp) == 2) ? $state_exp[1] : null;
            switch ($scope) {
                // make sure it is a valid catalogue
                case StoreCatalogSelector::ONE_CATALOG:
                    $sql = sprintf(
                        'select count(id) from Catalog where id = %s',
                        $this->db->quote($value, 'integer')
                    );

                    if (SwatDB::queryOne($this->db, $sql) == 0) {
                        $valid_state = false;

                        $selector->region = null;
                        $selector->catalog = null;
                        $selector->scope =
                            StoreCatalogSelector::ALL_ENABLED_CATALOGS;

                        unset($_SESSION['catalog']);
                    }
                    break;

                    // make sure it is a valid region
                case StoreCatalogSelector::ALL_ENABLED_CATALOGS_IN_REGION:
                    $sql = sprintf(
                        'select count(id) from Region where id = %s',
                        $this->db->quote($value, 'integer')
                    );

                    if (SwatDB::queryOne($this->db, $sql) == 0) {
                        $valid_state = false;

                        $selector->region = null;
                        $selector->catalog = null;
                        $selector->scope =
                            StoreCatalogSelector::ALL_ENABLED_CATALOGS;

                        unset($_SESSION['catalog']);
                    }
                    break;
            }

            if ($valid_state) {
                $selector->setState($state);
            }
        }
    }

    public function process()
    {
        parent::process();

        if ($this->getCompositeWidget('button')->hasBeenClicked()) {
            $_SESSION['catalog'] =
                $this->getCompositeWidget('selector')->getState();
        }
    }

    public function display()
    {
        parent::display();

        $div_tag = new SwatHtmlTag('div');
        $div_tag->class = 'catalog-switcher';
        $div_tag->open();

        $label_tag = new SwatHtmlTag('label');
        $label_tag->for = $this->id . '_selector';
        $label_tag->setContent(sprintf('%s:', Store::_('Catalog')));
        $label_tag->display();

        echo '&nbsp;';
        $this->getCompositeWidget('selector')->display();
        echo '&nbsp;';
        $this->getCompositeWidget('button')->display();

        $div_tag->close();
    }

    public function getSubQuery()
    {
        return $this->getCompositeWidget('selector')->getSubQuery();
    }

    protected function createCompositeWidgets()
    {
        $selector = new StoreCatalogSelector($this->id . '_selector');
        $selector->db = $this->db;
        $this->addCompositeWidget($selector, 'selector');

        $button = new SwatButton($this->id . '_switch_button');
        $button->title = Store::_('Switch');
        $this->addCompositeWidget($button, 'button');
    }
}
