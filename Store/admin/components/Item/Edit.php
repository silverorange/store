<?php

/**
 * Edit page for Items.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemEdit extends AdminDBEdit
{
    protected $product;
    protected $item;

    /**
     * Used to build the navbar.
     *
     * If the user navigated to this page from the Product Categories page then
     *  then this variable will be set and will cause the navbar to display
     *  differently.
     *
     * @var int
     */
    protected $category_id;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML($this->getUiXml());

        $this->product = SiteApplication::initVar('product');
        $this->category_id = SiteApplication::initVar('category');

        $this->initItem();

        if ($this->product === null && $this->item->id === null) {
            throw new AdminNoAccessException(Store::_(
                'A product ID or an item ID must be passed in the URL.'
            ));
        }

        $status_radiolist = $this->ui->getWidget('status');
        foreach (StoreItemStatusList::statuses() as $status) {
            $status_radiolist->addOption(
                new SwatOption($status->id, $status->title)
            );
        }

        $sale_discounts = SwatDB::getOptionArray(
            $this->app->db,
            'SaleDiscount',
            'title',
            'id',
            'title'
        );

        $sale_discount_flydown = $this->ui->getWidget('sale_discount');
        $sale_discount_flydown->addOptionsByArray($sale_discounts);
        $this->ui->getWidget('sale_discount_field')->visible =
            (count($sale_discounts) > 0);

        $group_flydown = $this->ui->getWidget('minimum_quantity_group');
        $options = SwatDB::getOptionArray(
            $this->app->db,
            'ItemMinimumQuantityGroup',
            'title',
            'id',
            'title'
        );

        $group_flydown->addOptionsByArray($options);
        $this->ui->getWidget('minimum_quantity_group_field')->visible =
            (count($options) > 0);

        $regions = SwatDB::getOptionArray(
            $this->app->db,
            'Region',
            'title',
            'id',
            'title'
        );

        $price_replicator = $this->ui->getWidget('price_replicator');
        $price_replicator->replicators = $regions;

        if ($this->ui->hasWidget('provstate_exclusion')) {
            $this->ui->getWidget('provstate_exclusion')->addOptionsByArray(
                SwatDB::getOptionArray(
                    $this->app->db,
                    'ProvState',
                    'title',
                    'id',
                    'country, title'
                )
            );
        }

        $form = $this->ui->getWidget('edit_form');
        $form->addHiddenField('product', $this->product);
    }

    protected function initItem()
    {
        $class_name = SwatDBClassMap::get('StoreItem');
        $this->item = new $class_name();
        $this->item->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->item->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(
                        Store::_('Item with id "%s" not found.'),
                        $this->id
                    )
                );
            }
            $this->product = $this->item->getInternalValue('product');
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/edit.xml';
    }

    // process phase

    public function process()
    {
        $this->processPriceReplicators();
        parent::process();
    }

    protected function processPriceReplicators()
    {
        /*
         * Pre-process "enabled" checkboxes to set required flag on price
         * entries.  Also set correct locale on the Price Entry.
         */
        $sql = 'select id, title from Region order by Region.id';
        $regions = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get('StoreRegionWrapper')
        );

        $replicator = $this->ui->getWidget('price_replicator');

        foreach ($regions as $region) {
            $this->processPriceReplicatorByRegion($replicator, $region);
        }
    }

    protected function processPriceReplicatorByRegion(
        SwatReplicableContainer $replicator,
        StoreRegion $region
    ) {
        $locale = $region->getFirstLocale()->id;

        $enabled = $replicator->getWidget('enabled', $region->id);
        $enabled->process();

        $price = $replicator->getWidget('price', $region->id);
        $price->required = $enabled->value;
        $price->locale = $locale;

        $original_price = $replicator->getWidget('original_price', $region->id);
        $original_price->locale = $locale;

        $sale_discount_price = $replicator->getWidget(
            'sale_discount_price',
            $region->id
        );

        $sale_discount_price->locale = $locale;
    }

    protected function saveDBData(): void
    {
        $this->updateItem();
        $this->item->save();

        $this->app->messages->add($this->getUpdateMessage());

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function updateItem()
    {
        $values = $this->ui->getValues([
            'description',
            'sku',
            'status',
            'part_unit',
            'part_count',
            'singular_unit',
            'plural_unit',
            'sale_discount',
            'minimum_quantity_group',
            'minimum_quantity',
            'minimum_multiple',
        ]);

        $this->item->sku = trim($values['sku']);
        $this->item->description = $values['description'];
        $this->item->part_unit = $values['part_unit'];
        $this->item->part_count = $values['part_count'];
        $this->item->singular_unit = $values['singular_unit'];
        $this->item->plural_unit = $values['plural_unit'];
        $this->item->sale_discount = $values['sale_discount'];
        $this->item->minimum_quantity_group = $values['minimum_quantity_group'];
        $this->item->minimum_quantity = $values['minimum_quantity'];
        $this->item->minimum_multiple = $values['minimum_multiple'];
        $this->item->product = $this->product;
        $this->item->setStatus(
            StoreItemStatusList::statuses()->getById($values['status'])
        );

        $this->updateRegionBindings();
        $this->updateProvstateExclusionBindings();
        $this->updateItemAliases();
    }

    protected function validate(): void
    {
        $sql = sprintf(
            'select catalog from Product where id = %s',
            $this->app->db->quote($this->product, 'integer')
        );

        $catalog = SwatDB::queryOne($this->app->db, $sql);

        // validate main sku
        $sku = $this->ui->getWidget('sku');
        $valid =
            ($this->item->sku !== null) ? [$this->item->sku] : [];

        if (!StoreItem::validateSku(
            $this->app->db,
            $sku->value,
            $catalog,
            $this->product,
            $valid
        )) {
            $sku->addMessage(new SwatMessage(
                Store::_('%s must be unique amongst all catalogs unless ' .
                'catalogs are clones of each other.')
            ));
        }

        // validate alias skus
        $aliases = $this->ui->getWidget('aliases');
        if (count($aliases->values)) {
            $invalid_skus = [];
            $valid_skus = [];

            foreach ($aliases->values as $alias) {
                /*
                 * Checks the following:
                 * - alias is valid wrt catalogue
                 * - alias is not the same as current item sku
                 * - two of the same aliases are not entered at once
                 */
                if (!StoreItem::validateSKU(
                    $this->app->db,
                    $alias,
                    $catalog,
                    $this->product,
                    $aliases->values
                )
                    || $alias == $sku->value || in_array($alias, $valid_skus)) {
                    $invalid_skus[] = $alias;
                } else {
                    $valid_skus[] = $alias;
                }
            }

            if (count($invalid_skus) > 0) {
                $message = new SwatMessage(
                    sprintf(Store::ngettext(
                        'The following alias SKU already exists: %s',
                        'The following alias SKUs already exist: %s',
                        count($invalid_skus)
                    ), implode(', ', $invalid_skus)),
                    'error'
                );

                $aliases->addMessage($message);
            }
        }
    }

    protected function updateRegionBindings()
    {
        // get old values before deleting the old bindings. If the
        // price_replicator doesn't include one of the old values, re-save
        // its previous state instead of resetting it to the database default.
        $old_values = $this->getRegionBindingsOldValues();

        $this->deleteRegionBindings();

        $price_replicator = $this->ui->getWidget('price_replicator');
        foreach ($price_replicator->replicators as $region_id => $title) {
            $region_binding = $this->getItemRegionBinding(
                $price_replicator,
                $region_id,
                $old_values
            );

            if ($region_binding instanceof StoreItemRegionBinding) {
                $this->item->region_bindings->add($region_binding);
            }
        }
    }

    protected function getRegionBindingsOldValues()
    {
        $old_values = [];

        foreach ($this->item->region_bindings as $binding) {
            $old_values[$binding->region->id]['enabled'] = $binding->enabled;
            $old_values[$binding->region->id]['price'] = $binding->price;
            $old_values[$binding->region->id]['original_price'] =
                $binding->original_price;

            $old_values[$binding->region->id]['sale_discount_price'] =
                $binding->sale_discount_price;
        }

        return $old_values;
    }

    protected function deleteRegionBindings()
    {
        // Due to SwatDBDataObject not being able to delete when there is no id
        // like the binding table below, this has to use manual sql to do its
        // delete, and can't use the nice removeAll() method.
        $delete_sql = 'delete from ItemRegionBinding where item = %s';
        $delete_sql = sprintf(
            $delete_sql,
            $this->app->db->quote($this->item->id, 'integer')
        );

        SwatDB::exec($this->app->db, $delete_sql);
    }

    protected function getItemRegionBinding(
        SwatReplicableContainer $replicator,
        $region_id,
        array $old_values
    ) {
        $region_binding = null;

        $price = $replicator->getWidget('price', $region_id);
        $enabled = $replicator->getWidget('enabled', $region_id);
        $original_price = $replicator->getWidget('original_price', $region_id);
        $sale_discount_price = $replicator->getWidget(
            'sale_discount_price',
            $region_id
        );

        // only create new binding if price exists, otherwise there is no
        // use for the binding, and it can lead to bad data on the site
        if ($price->getState() !== null) {
            $class_name = SwatDBClassMap::get('StoreItemRegionBinding');

            $region_binding = new $class_name();
            $region_binding->region = $region_id;
            $region_binding->price = $price->value;

            // Check visiblity of the widgets as enabled, original_price and
            // sale_discount_price are all optional.
            if ($this->isWidgetVisible($enabled)) {
                $region_binding->enabled = $enabled->value;
            } elseif (isset($old_values[$region_id])) {
                $region_binding->enabled = $old_values[$region_id]['enabled'];
            }

            if ($this->isWidgetVisible($original_price)) {
                $region_binding->original_price = $original_price->value;
            } elseif (isset($old_values[$region_id])) {
                $region_binding->original_price =
                    $old_values[$region_id]['original_price'];
            }

            if ($this->isWidgetVisible($sale_discount_price)) {
                $region_binding->sale_discount_price =
                    $sale_discount_price->value;
            } elseif (isset($old_values[$region_id])) {
                $region_binding->sale_discount_price =
                    $old_values[$region_id]['sale_discount_price'];
            }
        }

        return $region_binding;
    }

    protected function isWidgetVisible($widget)
    {
        $visible = ($widget !== null && $widget->visible);

        if (isset($widget->parent)) {
            $visible = $visible && $widget->parent->visible;
        }

        return $visible;
    }

    protected function updateProvstateExclusionBindings()
    {
        if (!$this->ui->hasWidget('provstate_exclusion')) {
            return;
        }

        SwatDB::updateBinding(
            $this->app->db,
            'ItemProvstateExclusionBinding',
            'item',
            $this->item->id,
            'provstate',
            $this->ui->getWidget('provstate_exclusion')->values,
            'ProvState',
            'ProvState.id'
        );
    }

    protected function updateItemAliases()
    {
        $this->item->item_aliases->removeAll();

        $aliases = $this->ui->getWidget('aliases');
        if (count($aliases->values)) {
            $class_name = SwatDBClassMap::get('StoreItemAlias');

            foreach ($aliases->values as $alias) {
                $item_alias = new $class_name();
                $item_alias->sku = $alias;
                $this->item->item_aliases->add($item_alias);
            }
        }
    }

    protected function getUpdateMessage()
    {
        return new SwatMessage(sprintf(
            Store::_('“%s” has been saved.'),
            $this->item->sku
        ));
    }

    // build phase

    protected function display()
    {
        parent::display();
        Swat::displayInlineJavaScript($this->getInlineJavaScript());
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        // get rid of the items component, and the edit navbar entries
        $this->navbar->popEntries(2);

        if ($this->category_id === null) {
            $this->navbar->addEntry(new SwatNavBarEntry(
                Store::_('Product Search'),
                'Product'
            ));
        } else {
            $this->navbar->addEntry(new SwatNavBarEntry(
                Store::_('Product Categories'),
                'Category'
            ));

            $cat_navbar_rs = SwatDB::executeStoredProc(
                $this->app->db,
                'getCategoryNavbar',
                [$this->category_id]
            );

            foreach ($cat_navbar_rs as $entry) {
                $this->navbar->addEntry(new SwatNavBarEntry(
                    $entry->title,
                    'Category/Index?id=' . $entry->id
                ));
            }
        }

        $product_title = SwatDB::queryOneFromTable(
            $this->app->db,
            'Product',
            'text:title',
            'id',
            $this->product
        );

        if ($this->category_id === null) {
            $link = sprintf('Product/Details?id=%s', $this->product);
        } else {
            $link = sprintf(
                'Product/Details?id=%s&category=%s',
                $this->product,
                $this->category_id
            );
        }

        $this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
        $this->title = $product_title;

        if ($this->item->id === null) {
            $this->navbar->addEntry(new SwatNavBarEntry(Store::_('New Item')));
        } else {
            $this->navbar->addEntry(new SwatNavBarEntry(Store::_('Edit Item')));
        }
    }

    protected function loadDBData()
    {
        $this->ui->setValues($this->item->getAttributes());
        $this->ui->getWidget('status')->value = $this->item->getStatus()->id;

        if ($this->item->sale_discount !== null) {
            $this->ui->getWidget('sale_discount')->value =
                $this->item->sale_discount->id;
        }

        if ($this->item->minimum_quantity_group !== null) {
            $this->ui->getWidget('minimum_quantity_group')->value =
                $this->item->minimum_quantity_group->id;
        }

        $this->loadRegionBindings();
        $this->loadProvstateExclusionBindings();
        $this->loadItemAliases();
    }

    protected function getInlineJavaScript()
    {
        $price_replicator = $this->ui->getWidget('price_replicator');
        $replicator_ids = array_keys($price_replicator->replicators);
        $replicator_ids = implode(', ', $replicator_ids);
        $form_id = 'edit_form';

        return sprintf(
            "var item_edit_page = new StoreItemEditPage('%s', [%s]);",
            $form_id,
            $replicator_ids
        );
    }

    protected function loadRegionBindings()
    {
        if ($this->item->id !== null) {
            $price_replicator = $this->ui->getWidget('price_replicator');

            // set all enabled to false on edits, as each region will set its
            // own enabled state in the next foreach loop.
            foreach ($price_replicator->replicators as $region_id => $title) {
                $enabled = $price_replicator->getWidget('enabled', $region_id);
                $enabled->value = false;
            }

            foreach ($this->item->region_bindings as $binding) {
                $this->loadPriceReplicatorByBinding(
                    $price_replicator,
                    $binding
                );
            }
        }
    }

    protected function loadPriceReplicatorByBinding(
        SwatReplicableContainer $replicator,
        StoreItemRegionBinding $binding
    ) {
        $enabled = $replicator->getWidget('enabled', $binding->region->id);
        $enabled->value = $binding->enabled;

        $price = $replicator->getWidget('price', $binding->region->id);
        $price->value = $binding->price;

        $original_price = $replicator->getWidget(
            'original_price',
            $binding->region->id
        );

        $original_price->value = $binding->original_price;

        $sale_discount_price = $replicator->getWidget(
            'sale_discount_price',
            $binding->region->id
        );

        $sale_discount_price->value = $binding->sale_discount_price;
    }

    protected function loadProvstateExclusionBindings()
    {
        if ($this->ui->hasWidget('provstate_exclusion')) {
            $widget = $this->ui->getWidget('provstate_exclusion');
            foreach ($this->item->provstate_exclusion_bindings as $binding) {
                $widget->values[] = $binding->getInternalValue('provstate');
            }
        }
    }

    private function loadItemAliases()
    {
        $aliases = $this->ui->getWidget('aliases');
        foreach ($this->item->item_aliases as $alias) {
            $aliases->values[] = $alias->sku;
        }
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();
        $yui = new SwatYUI(['dom', 'event']);
        $this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

        $this->layout->addHtmlHeadEntry(
            'packages/store/admin/javascript/store-item-edit-page.js'
        );

        $this->layout->addHtmlHeadEntry(
            'packages/store/admin/styles/store-item-edit-page.css'
        );
    }
}
