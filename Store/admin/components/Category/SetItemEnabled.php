<?php

/**
 * Enable items confirmation page for Categories.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategorySetItemEnabled extends AdminDBConfirmation
{
    private $category_id;
    private $enabled;
    private $region;
    private StoreCatalogSwitcher $catalog_switcher;

    public function setCategory($category_id)
    {
        $this->category_id = $category_id;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    public function setRegion($region)
    {
        $this->region = $region;
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->category_id = SiteApplication::initVar('category');
        $this->region = SiteApplication::initVar('region');
        $this->enabled = SiteApplication::initVar('enabled', false);

        $this->catalog_switcher = new StoreCatalogSwitcher();
        $this->catalog_switcher->db = $this->app->db;
        $this->catalog_switcher->init();
    }

    // process phase

    protected function processDBData(): void
    {
        parent::processDBData();

        $sql = sprintf(
            'update ItemRegionBinding set enabled = %s
			where price is not null and %s item in (%s)',
            $this->app->db->quote($this->enabled, 'boolean'),
            $this->getRegionQuerySQL(),
            $this->getItemQuerySQL()
        );

        SwatDB::exec($this->app->db, $sql);

        $rs = SwatDB::query($this->app->db, $this->getItemQuerySQL());
        $count = count($rs);

        $message = new SwatMessage(
            $this->getEnabledText('message', $count),
            'notice'
        );

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $rs = SwatDB::query($this->app->db, $this->getItemQuerySQL());
        $count = count($rs);

        if ($count == 0) {
            $this->switchToCancelButton();
            $message_text = Store::_(
                'There are no items in the selected categories.'
            );
        } else {
            $message_text = $this->getEnabledText('confirmation', $count);

            $this->ui->getWidget('yes_button')->title =
                $this->getEnabledText('button', $count);
        }

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $message_text;
        $message->content_type = 'text/xml';

        $form = $this->ui->getWidget('confirmation_form');
        $form->addHiddenField('category', $this->category_id);
        $form->addHiddenField('region', $this->region);

        // since we can't preserve type information when adding hidden fields
        if ($this->enabled) {
            $form->addHiddenField('enabled', $this->enabled);
        }
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $this->navbar->popEntry();

        if ($this->category_id !== null) {
            $navbar_rs = SwatDB::executeStoredProc(
                $this->app->db,
                'getCategoryNavbar',
                [$this->category_id]
            );

            foreach ($navbar_rs as $row) {
                $this->navbar->addEntry(new SwatNavBarEntry(
                    $row->title,
                    'Category/Index?id=' . $row->id
                ));
            }
        }

        $this->navbar->addEntry(new SwatNavBarEntry(
            $this->getEnabledText('navbar')
        ));
    }

    private function getItemQuerySQL()
    {
        $item_list = $this->getItemList('integer');

        $sql = 'select distinct Item.id
				from Item
					inner join Product on Product.id = Item.product
					inner join CategoryProductBinding on
						CategoryProductBinding.product = Product.id
					inner join getCategoryDescendants(null) as
						category_descendants on
						category_descendants.descendant =
							CategoryProductBinding.category
				where category_descendants.category in (%s)
					and Product.catalog in (%s)';

        return sprintf(
            $sql,
            $item_list,
            $this->catalog_switcher->getSubquery()
        );
    }

    private function getRegionQuerySQL()
    {
        $sql = '';

        if ($this->region > 0) {
            $sql = sprintf(
                'region = %s and',
                $this->app->db->quote($this->region, 'integer')
            );
        }

        return $sql;
    }

    private function getRegionTitle()
    {
        if ($this->region > 0) {
            $region_title = SwatDB::queryOne(
                $this->app->db,
                sprintf(
                    'select title from Region where id = %s',
                    $this->region
                )
            );
        } else {
            $region_title = Store::_('All Regions');
        }

        return $region_title;
    }

    private function getEnabledText($id, $count = 0)
    {
        if ($this->enabled) {
            switch ($id) {
                case 'button':
                    return Store::ngettext(
                        'Set Item as Enabled',
                        'Set Items as Enabled',
                        $count
                    );

                case 'confirmation':
                    return '<h3>' . sprintf(
                        Store::ngettext(
                            'If you proceed, one item will be enabled for “%2$s”.',
                            'If you proceed, %s items will be enabled for “%s”.',
                            $count
                        ),
                        SwatString::numberFormat($count),
                        $this->getRegionTitle()
                    ) . '</h3>';

                case 'message':
                    return sprintf(
                        Store::ngettext(
                            'One item has been enabled for “%2$s”.',
                            '%s items have been enabled for “%s”.',
                            $count
                        ),
                        SwatString::numberFormat($count),
                        $this->getRegionTitle()
                    );

                case 'navbar':
                    return Store::_('Enable Items Confirmation');

                default:
                    return null;
            }
        } else {
            switch ($id) {
                case 'button':
                    return Store::ngettext(
                        'Set Item as Disabled',
                        'Set Items as Disabled',
                        $count
                    );

                case 'confirmation':
                    return '<h3>' . sprintf(
                        Store::ngettext(
                            'If you proceed, one item will be disabled for “%2$s”.',
                            'If you proceed, %s items will be disabled for “%s”.',
                            $count
                        ),
                        SwatString::numberFormat($count),
                        $this->getRegionTitle()
                    ) . '</h3>';

                case 'message':
                    return sprintf(
                        Store::ngettext(
                            'One item has been disabled for “%2$s”.',
                            '%s items have been disabled for “%s”.',
                            $count
                        ),
                        SwatString::numberFormat($count),
                        $this->getRegionTitle()
                    );

                case 'navbar':
                    return Store::_('Disable Items Confirmation');

                default:
                    return null;
            }
        }
    }
}
