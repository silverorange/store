<?php

/**
 * Page for displaying a search form above search results.
 *
 * @copyright 2007-2016 silverorange
 */
class StoreSearchPage extends StoreSearchResultsPage
{
    /**
     * The user-interface of the search form.
     *
     * @var SwatUI
     */
    protected $form_ui;

    /**
     * The SwatML file to load the search user-interface from.
     *
     * @var string
     */
    protected $form_ui_xml = __DIR__ . '/search-form.xml';

    // init phase

    public function init()
    {
        parent::init();

        $this->form_ui = new SwatUI();
        $this->form_ui->loadFromXML($this->form_ui_xml);

        $form = $this->form_ui->getWidget('search_form');
        $form->action = $this->source;

        if ($this->form_ui->hasWidget('category')) {
            $category_flydown = $this->form_ui->getWidget('category');
            $categories = $this->getCategories();
            foreach ($categories as $category) {
                $category_flydown->addOption(
                    $category->shortname,
                    $category->title
                );
            }
        }

        $this->form_ui->init();
    }

    protected function getCategories()
    {
        $sql = 'select id, title, subtitle, shortname from Category
			where parent is null and id in
				(select category from VisibleCategoryView
				where region = %s or region is null)
			order by displayorder, title';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->app->getRegion()->id, 'integer')
        );

        return SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(StoreCategoryWrapper::class)
        );
    }

    // process phase

    public function process()
    {
        parent::process();

        $this->form_ui->process();

        /*
         * Nothing else to do...
         * the parent class result page is driven by the GET variables this
         * form provided.
         */
    }

    // build phase

    public function build()
    {
        $this->layout->startCapture('content');
        $this->form_ui->display();
        $this->layout->endCapture();

        parent::build();
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();
        $this->layout->addHtmlHeadEntrySet(
            $this->form_ui->getRoot()->getHtmlHeadEntrySet()
        );
    }
}
