<?php

/**
 * Details page for Products.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductDetails extends AdminIndex
{
    protected $id;
    protected $category_id;
    protected $product;

    /**
     * Cache of regions used by queryRegions().
     *
     * @var RegionsWrapper
     */
    private $regions;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML($this->getUiXml());

        $this->ui->getRoot()->addStyleSheet(
            'packages/store/admin/styles/store-product-details-page.css'
        );

        $this->ui->getRoot()->addStyleSheet(
            'packages/store/admin/styles/store-image-preview.css'
        );

        $this->id = SiteApplication::initVar('id');
        $this->product = $this->loadProduct();
        $this->category_id = SiteApplication::initVar(
            'category',
            null,
            SiteApplication::VAR_GET
        );

        $this->initItems();
    }

    protected function initItems()
    {
        if ($this->ui->hasWidget('items_frame')) {
            $this->ui->getWidget('item_group')->db = $this->app->db;
            $this->ui->getWidget('item_group')->product_id = $this->id;

            $regions = $this->queryRegions();
            $view = $this->ui->getWidget('items_view');

            // add dynamic columns to items view
            $this->appendPriceColumns($view, $regions);

            $sale_discount_flydown = $this->ui->getWidget('sale_discount_flydown');
            $sale_discount_flydown->addOptionsByArray(SwatDB::getOptionArray(
                $this->app->db,
                'SaleDiscount',
                'title',
                'id',
                'title'
            ));

            $flydown = $this->ui->getWidget('minimum_quantity_group_flydown');
            $options = SwatDB::getOptionArray(
                $this->app->db,
                'ItemMinimumQuantityGroup',
                'title',
                'id',
                'title'
            );

            $flydown->addOptionsByArray($options);
            $this->ui->getWidget('minimum_quantity_group')->visible =
                (count($options) > 0);
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/details.xml';
    }

    private function loadProduct()
    {
        $sql = sprintf(
            'select Product.*, ProductPrimaryCategoryView.primary_category,
				getCategoryPath(ProductPrimaryCategoryView.primary_category)
					as path
			from Product
				left outer join ProductPrimaryCategoryView
					on Product.id = ProductPrimaryCategoryView.product
			where id = %s',
            $this->app->db->quote($this->id, 'integer')
        );

        $row = SwatDB::queryRow($this->app->db, $sql);

        if ($row === null) {
            throw new AdminNotFoundException(
                sprintf(
                    Store::_('A product with an id of ‘%s’ does not exist.'),
                    $this->id
                )
            );
        }

        $product_class = SwatDBClassMap::get(StoreProduct::class);
        $product = new $product_class($row);
        $product->setDatabase($this->app->db);

        return $product;
    }

    // process phase

    protected function processInternal()
    {
        parent::processInternal();

        // related products
        $related_products_form = $this->ui->getWidget('related_products_form');
        $related_products_view = $this->ui->getWidget('related_products_view');
        if ($related_products_form->isProcessed()
            && count($related_products_view->getSelection()) != 0) {
            $this->processRelatedProducts($related_products_view);
        }

        // product collections
        $form = $this->ui->getWidget('product_collection_form');
        $view = $this->ui->getWidget('product_collection_view');
        if ($form->isProcessed() && count($view->getSelection()) != 0) {
            $this->processProductCollections($view);
        }

        // add new items
        if ($this->ui->hasWidget('items_frame')
            && $this->ui->getWidget('index_actions')->selected !== null
            && $this->ui->getWidget('index_actions')->selected->id == 'add') {
            $this->addNewItems();
        }
    }

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($view->id) {
            case 'items_view':
                $this->processItemsActions($view, $actions);
                break;

            case 'related_articles_view':
                $this->processRelatedArticleActions($view, $actions);
                break;
        }
    }

    protected function processItemsActions(SwatTableView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('Item/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;

            case 'change_group':
                $item_group_action = $this->ui->getWidget('item_group');
                $message = $item_group_action->processAction($view->getSelection());
                $this->app->messages->add($message);
                break;

            case 'change_status':
                $new_status = $this->ui->getWidget('status')->value;
                $this->changeStatus($view, $new_status);
                break;

            case 'enable':
                $region = $this->ui->getWidget('enable_region')->value;

                $sql = 'update ItemRegionBinding set enabled = %s
				where price is not null and %s item in (%s)';

                $region_sql = ($region > 0) ?
                    sprintf('region = %s and', $this->app->db->quote(
                        $region,
                        'integer'
                    )) : '';

                SwatDB::exec($this->app->db, sprintf(
                    $sql,
                    $this->app->db->quote(true, 'boolean'),
                    $region_sql,
                    SwatDB::implodeSelection(
                        $this->app->db,
                        $view->getSelection()
                    )
                ));

                $num = count($view->getSelection());

                $message = new SwatMessage(sprintf(
                    Store::ngettext(
                        'One item has been enabled.',
                        '%s items have been enabled.',
                        $num
                    ),
                    SwatString::numberFormat($num)
                ));

                $this->app->messages->add($message);
                break;

            case 'disable':
                $region = $this->ui->getWidget('disable_region')->value;

                $sql = 'update ItemRegionBinding set enabled = %s
				where %s item in (%s)';

                $region_sql = ($region > 0) ?
                    sprintf('region = %s and', $this->app->db->quote(
                        $region,
                        'integer'
                    )) : '';

                SwatDB::exec($this->app->db, sprintf(
                    $sql,
                    $this->app->db->quote(false, 'boolean'),
                    $region_sql,
                    SwatDB::implodeSelection(
                        $this->app->db,
                        $view->getSelection()
                    )
                ));

                $num = count($view->getSelection());

                $message = new SwatMessage(sprintf(
                    Store::ngettext(
                        'One item has been disabled.',
                        '%s items have been disabled.',
                        $num
                    ),
                    SwatString::numberFormat($num)
                ));

                $this->app->messages->add($message);
                break;

            case 'sale_discount':
                $sale_discount =
                    $this->ui->getWidget('sale_discount_flydown')->value;

                if ($sale_discount === null) {
                    break;
                }

                $count = SwatDB::updateColumn(
                    $this->app->db,
                    'Item',
                    'integer:sale_discount',
                    $sale_discount,
                    'id',
                    $view->getSelection()
                );

                $message = new SwatMessage(sprintf(
                    Store::ngettext(
                        'A sale discount has been applied to one item.',
                        'A sale discount has been applied to %s items.',
                        $count
                    ),
                    SwatString::numberFormat($count)
                ));

                $this->app->messages->add($message);

                break;

            case 'remove_sale_discount':
                $num = SwatDB::queryOne($this->app->db, sprintf(
                    'select count(id) from Item where id in (%s)
					and sale_discount is not null',
                    SwatDB::implodeSelection(
                        $this->app->db,
                        $view->getSelection()
                    )
                ));

                if ($num > 0) {
                    $count = SwatDB::updateColumn(
                        $this->app->db,
                        'Item',
                        'integer:sale_discount',
                        null,
                        'id',
                        $view->getSelection()
                    );

                    SwatDB::updateColumn(
                        $this->app->db,
                        'ItemRegionBinding',
                        'float:sale_discount_price',
                        null,
                        'item',
                        $view->getSelection()
                    );

                    $message = new SwatMessage(sprintf(
                        Store::ngettext(
                            'A sale discount has been removed from one item.',
                            'A sale discount has been removed from %s items.',
                            $count
                        ),
                        SwatString::numberFormat($count)
                    ));

                    $this->app->messages->add($message);
                } else {
                    $this->app->messages->add(new SwatMessage(Store::_(
                        'None of the items selected had a sale discount.'
                    )));
                }

                break;

            case 'minimum_quantity_group':
                $minimum_quantity_group =
                    $this->ui->getWidget('minimum_quantity_group_flydown')->value;

                if ($minimum_quantity_group === null) {
                    break;
                }

                $count = SwatDB::updateColumn(
                    $this->app->db,
                    'Item',
                    'integer:minimum_quantity_group',
                    $minimum_quantity_group,
                    'id',
                    $view->getSelection()
                );

                $message = new SwatMessage(sprintf(
                    Store::ngettext(
                        'A minimum quantity sale group has been ' .
                            'applied to one item.',
                        'A minimum quantity sale group has been ' .
                            'applied to %s items.',
                        $count
                    ),
                    SwatString::numberFormat($count)
                ));

                $this->app->messages->add($message);

                break;

            case 'remove_minimum_quantity_group':
                $num = SwatDB::queryOne($this->app->db, sprintf(
                    'select count(id) from Item where id in (%s)
					and minimum_quantity_group is not null',
                    SwatDB::implodeSelection(
                        $this->app->db,
                        $view->getSelection()
                    )
                ));

                if ($num > 0) {
                    $count = SwatDB::updateColumn(
                        $this->app->db,
                        'Item',
                        'integer:minimum_quantity_group',
                        null,
                        'id',
                        $view->getSelection()
                    );

                    $message = new SwatMessage(sprintf(
                        Store::ngettext(
                            'A minimum quantity sale group has been ' .
                                'removed from one item.',
                            'A minimum quantity sale group has been ' .
                                'removed from %s items.',
                            $count
                        ),
                        SwatString::numberFormat($count)
                    ));

                    $this->app->messages->add($message);
                } else {
                    $this->app->messages->add(new SwatMessage(Store::_(
                        'None of the items selected had a minimum ' .
                        'quantity sale group.'
                    )));
                }

                break;
        }

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function validateItemRows($input_row, $catalog)
    {
        $validate = true;
        $replicators = $input_row->getReplicators();

        foreach ($replicators as $replicator_id) {
            // validate sku
            $sku_widget = $input_row->getWidget('sku', $replicator_id);
            if (!StoreItem::validateSku(
                $this->app->db,
                $sku_widget->getState(),
                $catalog,
                $this->id
            )) {
                $sku_widget->addMessage(new SwatMessage(
                    Store::_('%s must be unique amongst all catalogs unless ' .
                    'catalogs are clones of each other.')
                ));

                $validate = false;
            }
        }

        return $validate;
    }

    protected function addNewItemExtras($item_id)
    {
        /*
         * this is a placeholder function, for the occasional case where a site
         * would require that we insert rows into other tables on item creation
         */
    }

    final protected function changeStatus(SwatTableView $view, $status)
    {
        $num = count($view->getSelection());

        SwatDB::updateColumn(
            $this->app->db,
            'Item',
            'integer:status',
            $status,
            'id',
            $view->getSelection()
        );

        $message = new SwatMessage(sprintf(
            Store::ngettext(
                'The status of one item has been changed.',
                'The status of %s items has been changed.',
                $num
            ),
            SwatString::numberFormat($num)
        ));

        $this->app->messages->add($message);
    }

    protected function addNewItems()
    {
        $sql = sprintf(
            'select catalog from Product where id = %s',
            $this->app->db->quote($this->id, 'integer')
        );

        $catalog = SwatDB::queryOne($this->app->db, $sql);

        $view = $this->ui->getWidget('items_view');
        $input_row = $view->getRow('input_row');

        $regions = $this->queryRegions();

        $fields = [
            'text:sku',
            'text:description',
            'integer:product',
        ];

        $item_region_fields = [
            'integer:item',
            'integer:region',
            'decimal:price',
            'boolean:enabled',
        ];

        if ($this->validateItemRows($input_row, $catalog)) {
            $new_skus = [];
            $replicators = $input_row->getReplicators();
            foreach ($replicators as $replicator_id) {
                if (!$input_row->rowHasMessage($replicator_id)) {
                    $sku = $input_row->getWidget(
                        'sku',
                        $replicator_id
                    )->value;

                    $description = $input_row->getWidget(
                        'description',
                        $replicator_id
                    )->value;

                    // Create new item
                    $values = [
                        'sku'         => $sku,
                        'description' => $description,
                        'product'     => $this->id,
                    ];

                    $item_id = SwatDB::insertRow(
                        $this->app->db,
                        'Item',
                        $fields,
                        $values,
                        'id'
                    );

                    foreach ($regions as $region) {
                        $price = $input_row->getWidget(
                            'price_' . $region->id,
                            $replicator_id
                        );

                        if ($price->getState() !== null) {
                            // Create new item_region binding
                            $item_region_values = ['item' => $item_id,
                                'region'                  => $region->id,
                                'price'                   => $price->getState(),
                                'enabled'                 => true];

                            SwatDB::insertRow(
                                $this->app->db,
                                'ItemRegionBinding',
                                $item_region_fields,
                                $item_region_values
                            );
                        }
                    }

                    $this->addNewItemExtras($item_id);

                    // remove the row after we entered it
                    $input_row->removeReplicatedRow($replicator_id);

                    $new_skus[] = SwatString::minimizeEntities($sku);
                }
            }

            if (count($new_skus) == 1) {
                $message = new SwatMessage(sprintf(
                    Store::_('“%s” has been added.'),
                    $new_skus[0]
                ));

                $this->app->messages->add($message);
            } elseif (count($new_skus) > 1) {
                $sku_list = '<ul><li>' . implode('</li><li>', $new_skus) .
                    '</li></ul>';

                $message = new SwatMessage(
                    Store::_('The following items have been added:')
                );

                $message->secondary_content = $sku_list;
                $message->content_type = 'text/xml';
                $this->app->messages->add($message);
            }
        } else {
            $message = new SwatMessage(
                Store::_('There was a problem adding ' .
                'the item(s). Please check the highlighted fields below.'),
                'error'
            );

            $this->app->messages->add($message);
        }
    }

    private function processRelatedProducts($view)
    {
        $this->app->replacePage('Product/RelatedProductDelete');
        $this->app->getPage()->setItems($view->getSelection());
        $this->app->getPage()->setId($this->id);
        $this->app->getPage()->setCategory($this->category_id);
    }

    private function processProductCollections($view)
    {
        $this->app->replacePage('Product/ProductCollectionDelete');
        $this->app->getPage()->setItems($view->getSelection());
        $this->app->getPage()->setId($this->id);
        $this->app->getPage()->setCategory($this->category_id);
    }

    private function processRelatedArticleActions($view, $actions)
    {
        $num = count($view->getSelection());
        $message = null;

        switch ($actions->selected->id) {
            case 'related_article_remove':
                $item_list = [];
                foreach ($view->getSelection() as $item) {
                    $item_list[] = $this->app->db->quote($item, 'integer');
                }

                $num = SwatDB::exec($this->app->db, sprintf(
                    '
				delete from ArticleProductBinding
				where product = %s and article in (%s)',
                    $this->app->db->quote($this->id, 'integer'),
                    implode(',', $item_list)
                ));

                $message = new SwatMessage(sprintf(Store::ngettext(
                    'One related article has been removed from this product.',
                    '%s related articles have been removed from this product.',
                    $num
                ), SwatString::numberFormat($num)));
        }

        if ($message !== null) {
            $this->app->messages->add($message);
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();
        $this->buildProduct();
        $this->buildItems();
        $this->buildProductImages();
        $this->buildRelatedProducts();
        $this->buildProductCollections();
        $this->buildRelatedArticles();
    }

    protected function buildForms()
    {
        parent::buildForms();

        // always show add new item action regardless of entries in item table
        // but also keep all other actions hidden
        if ($this->ui->hasWidget('items_frame')
            && count($this->ui->getWidget('items_view')->model) == 0) {
            $index_actions = $this->ui->getWidget('index_actions');
            $index_actions->visible = true;
            foreach ($index_actions->getActionItems() as $id => $widget) {
                if ($widget->id !== 'add') {
                    $widget->visible = false;
                }
            }
        }
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        switch ($view->id) {
            case 'items_view':
                return $this->getItemsTableModel($view);

            case 'related_products_view':
                return $this->getRelatedProductsTableModel($view);

            case 'product_collection_view':
                return $this->getProductCollectionsTableModel($view);

            case 'related_articles_view':
                return $this->getRelatedArticlesTableModel($view);
        }

        return null;
    }

    protected function buildCategoryToolBarLinks(SwatToolBar $toolbar)
    {
        if ($this->category_id === null) {
            $toolbar->setToolLinkValues($this->id);
        } else {
            foreach ($toolbar->getToolLinks() as $tool_link) {
                if (mb_substr($tool_link->link, -5) === 'id=%s'
                    || mb_substr($tool_link->link, -10) === 'product=%s') {
                    $tool_link->link .= '&category=%s';
                }
            }

            $toolbar->setToolLinkValues([$this->id, $this->category_id]);
        }
    }

    protected function buildCategoryTableViewLinks(SwatTableView $view)
    {
        if ($this->category_id !== null) {
            $link_suffix = sprintf('&category=%s', $this->category_id);
            foreach ($view->getColumns() as $column) {
                foreach ($column->getRenderers() as $renderer) {
                    if ($renderer instanceof SwatLinkCellRenderer) {
                        $renderer->link .= $link_suffix;
                    }
                }
            }

            foreach ($view->getGroups() as $group) {
                foreach ($group->getRenderers() as $renderer) {
                    if ($renderer instanceof SwatLinkCellRenderer) {
                        $renderer->link .= $link_suffix;
                    }
                }
            }
        }
    }

    // build phase - product details

    protected function getProductDetailsStore($product)
    {
        $ds = new SwatDetailsStore($product);

        if (count($product->categories) === 0) {
            $ds->categories = null;
        } else {
            ob_start();
            $this->displayCategories($product->categories, true);
            $ds->categories = ob_get_clean();
        }

        if (count($product->featured_categories) === 0) {
            $ds->featured_categories = null;
        } else {
            ob_start();
            $this->displayCategories($product->featured_categories);
            $ds->featured_categories = ob_get_clean();
        }

        // format the bodytext
        $ds->bodytext = SwatString::condense(SwatString::toXHTML(
            $product->bodytext
        ));

        $ds->attributes = $this->buildAttributes($product);

        return $ds;
    }

    protected function buildAttributes($product)
    {
        if ($product->attributes === null) {
            return;
        }

        $types = SwatDB::query(
            $this->app->db,
            'select * from attributetype order by shortname',
            SwatDBClassMap::get(StoreAttributeTypeWrapper::class)
        );

        $count = 0;
        ob_start();

        foreach ($types as $type) {
            $attributes = $product->attributes->getByType($type->shortname);

            if (count($attributes) > 0) {
                if ($count > 0) {
                    echo '</ul>';
                }

                echo ucfirst($type->shortname), ':';
                echo '<ul>';

                foreach ($attributes as $attribute) {
                    echo '<li>';
                    $this->displayAttribute($attribute);
                    echo '</li>';
                }

                echo '</ul>';
            }
            $count++;
        }

        return ob_get_clean();
    }

    protected function displayAttribute(StoreAttribute $attribute)
    {
        $attribute->display();
    }

    protected function buildProductNavBar($product)
    {
        if ($this->category_id !== null) {
            // use category navbar
            $this->navbar->popEntry();
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

        $this->navbar->addEntry(new SwatNavBarEntry($product->title));
    }

    protected function displayCategories(
        StoreCategoryWrapper $categories,
        $display_canonical = false
    ) {
        $primary_category = $this->product->primary_category;
        $multiple_categories = (count($categories) > 1);

        echo '<ul class="product-categories">';

        foreach ($categories as $category) {
            $navbar = new SwatNavBar();
            $navbar->separator = ' › ';
            $navbar->addEntries($category->getAdminNavBarEntries());

            echo '<li>';
            $navbar->display();

            if ($display_canonical
                && $multiple_categories
                && $category->id === $primary_category->id) {
                $span_tag = new SwatHtmlTag('span');
                $span_tag->class = 'canonical-category';
                $span_tag->setContent(Store::_('Canonical Path'));
                $span_tag->display();
            }

            echo '</li>';
        }

        echo '</ul>';
    }

    protected function buildProduct()
    {
        $ds = $this->getProductDetailsStore($this->product);
        $details_view = $this->ui->getWidget('details_view');
        $details_view->data = $ds;

        $details_frame = $this->ui->getWidget('details_frame');
        $details_frame->title = Store::_('Product');
        $details_frame->subtitle = $this->product->title;
        $this->title = $this->product->title;

        $toolbar = $this->ui->getWidget('details_toolbar');
        $this->buildCategoryToolBarLinks($toolbar);
        $this->buildViewInStoreToolLinks($this->product);
        $this->buildProductNavBar($this->product);
    }

    protected function buildViewInStoreToolLinks(StoreProduct $product)
    {
        $regions = $this->queryRegions();
        $region_count = count($regions);

        // do this before the category check, so that the prototype tool link
        // gets removed no matter what.
        $prototype_tool_link = $this->ui->getWidget('view_in_store');
        $toolbar = $prototype_tool_link->parent;
        $toolbar->remove($prototype_tool_link);

        foreach ($this->regions as $region) {
            $locale = $region->getFirstLocale();
            if ($locale !== null) {
                $sql = sprintf(
                    'select product from VisibleProductView
					where region = %s and product = %s',
                    $this->app->db->quote($region->id, 'integer'),
                    $this->app->db->quote($product->id, 'integer')
                );

                $visible_in_region =
                    (SwatDB::queryOne($this->app->db, $sql) !== null);

                $tool_link = clone $prototype_tool_link;
                $tool_link->id .= '_' . $region->id;

                if ($region_count > 1) {
                    $tool_link->value = $locale->getURLLocale() .
                        $this->app->config->store->path .
                        $product->path;

                    $tool_link->title .= sprintf(' (%s)', $region->title);
                } else {
                    $tool_link->value = $this->app->config->store->path .
                        $product->path;
                }

                // since we check the VisibleProductView for this, this will
                // work on both sites that allow orphan products and those that
                // don't
                if (!$visible_in_region) {
                    $tool_link->sensitive = false;
                }

                $toolbar->packEnd($tool_link);
            }
        }
    }

    // build phase - items

    protected function buildItems()
    {
        if ($this->ui->hasWidget('items_frame')) {
            $view = $this->ui->getWidget('items_view');
            $toolbar = $this->ui->getWidget('items_toolbar');
            $form = $this->ui->getWidget('items_form');
            $view->addStyleSheet(
                'packages/store/admin/styles/disabled-rows.css'
            );

            $form->action = $this->getRelativeURL();

            $this->buildItemGroups();

            // show default status for new items if there is an input row
            if ($view->getFirstRowByClass('SwatTableViewInputRow') !== null) {
                $column = $view->getColumn('status');
                $input_status = $column->getInputCell()->getPrototypeWidget();
                $input_status->content =
                    StoreItemStatusList::status('available')->title;
            }

            $this->buildStatusList();

            // setup the flydowns for enabled/disabled actions
            $regions = SwatDB::getOptionArray(
                $this->app->db,
                'Region',
                'title',
                'id'
            );

            $regions[0] = Store::_('All Regions');

            $this->ui->getWidget('enable_region')->addOptionsByArray($regions);
            $this->ui->getWidget('disable_region')->addOptionsByArray($regions);

            $quantity_discounts = $view->getColumn('quantity_discounts');
            $quantity_discounts->getRendererByPosition()->db = $this->app->db;

            $this->buildCategoryToolBarLinks($toolbar);
            $this->buildCategoryTableViewLinks($view);
        }
    }

    protected function buildStatusList()
    {
        foreach (StoreItemStatusList::statuses() as $status) {
            $this->ui->getWidget('status')->addOption(
                new SwatOption($status->id, $status->title)
            );
        }
    }

    protected function getItemsTableModel(SwatTableView $view): SwatTableStore
    {
        $sql = $this->getItemsSql($view);
        $items = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(StoreItemWrapper::class)
        );

        $store = new SwatTableStore();

        foreach ($items as $item) {
            $ds = new SwatDetailsStore($item);

            $ds->description = $this->getItemDescription($item);
            $ds->status = $item->getStatus();

            $ds->item_group_title = ($item->item_group === null) ?
                Store::_('[Ungrouped]') : $item->item_group->title;

            $ds->item_group_id = ($item->item_group === null) ?
                0 : $item->item_group->id;

            $enabled = false;

            foreach ($this->queryRegions() as $region) {
                $price_field_name = sprintf('price_%s', $region->id);
                $enabled_field_name = sprintf('enabled_%s', $region->id);
                $savings_field_name = sprintf('savings_%s', $region->id);
                $is_on_sale_field_name = sprintf(
                    'is_on_sale_%s',
                    $region->id
                );

                $original_price_field_name = sprintf(
                    'original_price_%s',
                    $region->id
                );

                $ds->{$price_field_name} = $item->getDisplayPrice($region);
                $ds->{$original_price_field_name} = $item->getPrice($region);
                $ds->{$enabled_field_name} = $item->isEnabled($region);

                $ds->{$savings_field_name} = $ds->{$original_price_field_name} > 0 ?
                    1 - round($ds->{$price_field_name} / $ds->{$original_price_field_name}, 2) :
                    null;

                $ds->{$is_on_sale_field_name} =
                    $ds->{$price_field_name} != $ds->{$original_price_field_name};

                $enabled = $enabled || $ds->{$enabled_field_name};
            }

            $ds->enabled = $enabled;

            $store->add($ds);
        }

        return $store;
    }

    protected function getItemsSql(SwatTableView $view)
    {
        /*
         * This dynamic SQL is needed to make the table orderable by the price
         * columns.
         */
        $regions = $this->queryRegions();

        $regions_join_base =
            'left outer join ItemRegionBinding as ItemRegionBinding_%1$s
				on ItemRegionBinding_%1$s.item = Item.id
					and ItemRegionBinding_%1$s.region = %2$s';

        $regions_select_base = 'ItemRegionBinding_%s.price as price_%s';

        $regions_join = '';
        $regions_select = '';
        foreach ($regions as $region) {
            $regions_join .= sprintf(
                $regions_join_base,
                $region->id,
                $this->app->db->quote($region->id, 'integer')
            ) . ' ';

            $regions_select .= sprintf(
                $regions_select_base,
                $region->id,
                $this->app->db->quote($region->id, 'integer')
            ) . ', ';
        }

        $sql = 'select Item.*,
					-- regions select piece goes here
					%s
					-- put ungrouped items at the top
					coalesce(ItemGroup.displayorder, -1) as group_order
				from Item
					left outer join ItemGroup
						on ItemGroup.id = Item.item_group
					-- region join piece goes here
					%s
				where Item.product = %s
				order by group_order, ItemGroup.title, Item.item_group, %s';

        return sprintf(
            $sql,
            $regions_select,
            $regions_join,
            $this->app->db->quote($this->id, 'integer'),
            $this->getOrderByClause($view, $this->getItemsOrderByClause())
        );
    }

    protected function getItemsOrderByClause()
    {
        return 'Item.displayorder, Item.sku';
    }

    protected function getItemDescription(StoreItem $item)
    {
        return implode(' - ', $item->getDescriptionArray());
    }

    final protected function queryRegions()
    {
        if ($this->regions === null) {
            $sql = 'select id, title from Region order by id';

            $this->regions = SwatDB::query(
                $this->app->db,
                $sql,
                SwatDBClassMap::get(StoreRegionWrapper::class)
            );
        }

        return $this->regions;
    }

    private function buildItemGroups()
    {
        $view = $this->ui->getWidget('items_view');
        $group_header = $view->getGroup('group');
        $groups = $this->queryItemGroups();
        $has_items = (count($groups) > 0);

        // if there is one row and the groupnum is 0 then there are no
        // item_groups with items in them for this product. If $groups has 0
        // elements, there are no items

        if (count($groups) == 1 && $groups->getFirst()->item_group == 0) {
            $num_groups = 0;
        } elseif ($groups->getFirst()->item_group == 0) {
            $num_groups = count($groups) - 1;
        } else {
            $num_groups = count($groups);
        }

        $group_info = [];
        foreach ($groups as $group) {
            $group_info[$group->item_group] = $group->num_items;
        }

        $group_header->group_info = $group_info;

        $order_link = $this->ui->getWidget('items_order');

        if ($has_items) {
            if ($num_groups == 0) {
                // order items link orders items
                // and don't show group headers
                $group_header->visible = false;
                // order link is insensitive if there is only 1 item
                $order_link->sensitive = ($groups->getFirst()->num_items > 1);
            } elseif ($num_groups == 1) {
                // order groups link is not sensitive.
                // order items through the group header
                $order_link->title = Store::_('Change Group Order');
                $order_link->sensitive = false;
            } else {
                // order items link orders item_groups
                $order_link->title = Store::_('Change Group Order');
                $order_link->link = 'ItemGroup/Order?product=%s';
            }
        } else {
            $order_link->sensitive = false;
        }
    }

    private function queryItemGroups()
    {
        // get information about item groups used in this product
        $sql = 'select
					-- coalesce to 0 to match select query in getTableView()
					coalesce(item_group, 0) as item_group,
					count(id) as num_items
				from Item where
					product = %s and
					(item_group is null or
					item_group in (select id from ItemGroup where product = %s))
				group by item_group
				-- make sure the empty group is first
				order by item_group desc';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->id, 'integer'),
            $this->app->db->quote($this->id, 'integer')
        );

        return SwatDB::query($this->app->db, $sql);
    }

    private function appendPriceColumns(SwatTableView $view, $regions)
    {
        $region_count = count($regions);

        foreach ($regions as $region) {
            $column = new SwatTableViewOrderableColumn('price_' . $region->id);
            $column->title = sprintf(
                Store::ngettext(
                    'Price',
                    '%s Price',
                    $region_count
                ),
                $region->title
            );

            // discount renderer (only displayed if sale-discount is set)
            $discount_renderer = new SwatPercentageCellRenderer();
            $discount_renderer->locale = $region->getFirstLocale()->id;
            $column->addRenderer($discount_renderer);

            $column->addMappingToRenderer(
                $discount_renderer,
                'savings_' . $region->id,
                'value'
            );

            $column->addMappingToRenderer(
                $discount_renderer,
                'is_on_sale_' . $region->id,
                'visible'
            );

            // " Off" cell renderer (only displayed if sale-discount is set)
            $off_renderer = new SwatTextCellRenderer();
            $off_renderer->text = Store::_(' Off');
            $column->addRenderer($off_renderer);
            $column->addMappingToRenderer(
                $off_renderer,
                'is_on_sale_' . $region->id,
                'visible'
            );

            // original price renderer (only displayed if sale-discount is set)
            $sale_renderer = new StorePriceCellRenderer();
            $sale_renderer->locale = $region->getFirstLocale()->id;
            $sale_renderer->classes[] = 'store-sale-discount-original-price';
            $column->addRenderer($sale_renderer);

            $column->addMappingToRenderer(
                $sale_renderer,
                'original_price_' . $region->id,
                'value'
            );

            $column->addMappingToRenderer(
                $sale_renderer,
                'is_on_sale_' . $region->id,
                'visible'
            );

            // price renderer
            $price_renderer = new StoreAdminItemPriceCellRenderer();
            $price_renderer->locale = $region->getFirstLocale()->id;
            $column->addRenderer($price_renderer);

            $column->addMappingToRenderer(
                $price_renderer,
                'price_' . $region->id,
                'value'
            );

            $column->addMappingToRenderer(
                $price_renderer,
                'singular_unit',
                'singular_unit'
            );

            $column->addMappingToRenderer(
                $price_renderer,
                'plural_unit',
                'plural_unit'
            );

            $column->addMappingToRenderer(
                $price_renderer,
                'enabled_' . $region->id,
                'enabled'
            );

            $money_entry = new SwatMoneyEntry('input_price_' . $region->id);
            $money_entry->locale = $region->getFirstLocale()->id;
            $money_entry->size = 4;

            // add input cells if view has an input row
            if ($view->getFirstRowByClass('SwatTableViewInputRow') !== null) {
                $cell = new SwatInputCell();
                $cell->setWidget($money_entry);

                $column->setInputCell($cell);

                $view->appendColumn($column);

                // need to manually init here
                $column->init();
            }
        }
    }

    // build phase - product images

    protected function buildProductImages()
    {
        $toolbar = $this->ui->getWidget('product_images_toolbar');
        $this->buildCategoryToolBarLinks($toolbar);

        $images = $this->getProductImages();
        $form = $this->ui->getWidget('product_images_form');

        $order_link = $this->ui->getWidget('image_order');
        $order_link->sensitive = (count($images) > 1);

        foreach ($images as $image) {
            $widget = $this->getProductImageDisplay();
            $widget->image_id = $image->id;
            $widget->category_id = $this->category_id;
            $widget->product_id = $this->id;
            $widget->image = $image->getUri('thumb', '../');
            $widget->width = $image->getWidth('thumb');
            $widget->height = $image->getHeight('thumb');
            $widget->alt = '';

            $form->addChild($widget);
        }
    }

    protected function getProductImageDisplay()
    {
        return new StoreProductImageDisplay();
    }

    private function getProductImages()
    {
        $sql = 'select * from Image
			inner join ProductImageBinding on
				ProductImageBinding.image = Image.id
			where ProductImageBinding.product = %s
			order by displayorder';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(StoreProductImageWrapper::class)
        );
    }

    // build phase - related products

    private function buildRelatedProducts()
    {
        $toolbar = $this->ui->getWidget('related_products_toolbar');
        $view = $this->ui->getWidget('related_products_view');
        $this->buildCategoryToolBarLinks($toolbar);
        $this->buildCategoryTableViewLinks($view);
    }

    private function getRelatedProductsTableModel(
        SwatTableView $view
    ): SwatDBDefaultRecordsetWrapper {
        $sql = 'select id, title
			from Product
				inner join ProductRelatedProductBinding on id = related_product
					and source_product = %s
			order by title';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->id, 'integer')
        );

        $rs = SwatDB::query($this->app->db, $sql);

        if (count($rs) == 0) {
            $view->visible = false;
            $this->ui->getWidget('related_products_footer')->visible = false;
        }

        return $rs;
    }

    // build phase - product collections

    private function buildProductCollections()
    {
        $toolbar = $this->ui->getWidget('product_collection_toolbar');
        $view = $this->ui->getWidget('product_collection_view');
        $this->buildCategoryToolBarLinks($toolbar);
        $this->buildCategoryTableViewLinks($view);
    }

    private function getProductCollectionsTableModel(
        SwatTableView $view
    ): SwatDBDefaultRecordsetWrapper {
        $sql = 'select id, title
			from Product
				inner join ProductCollectionBinding on id = member_product
					and source_product = %s
			order by title';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->id, 'integer')
        );

        $rs = SwatDB::query($this->app->db, $sql);

        if (count($rs) == 0) {
            $view->visible = false;
            $this->ui->getWidget('product_collection_footer')->visible = false;
        }

        return $rs;
    }

    // build phase - related articles

    private function buildRelatedArticles()
    {
        $toolbar = $this->ui->getWidget('related_articles_toolbar');
        $view = $this->ui->getWidget('related_articles_view');
        $this->buildCategoryToolBarLinks($toolbar);
        $this->buildCategoryTableViewLinks($view);
    }

    private function getRelatedArticlesTableModel(
        SwatTableView $view
    ): SwatDBDefaultRecordsetWrapper {
        $sql = 'select Article.id,
					Article.title
				from Article
				inner join ArticleProductBinding
					on Article.id = ArticleProductBinding.article
				where ArticleProductBinding.product = %s
				order by %s';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->id, 'integer'),
            $this->getOrderByClause($view, 'Article.title', 'Article')
        );

        $rs = SwatDB::query($this->app->db, $sql);

        if (count($rs) == 0) {
            $index_form = $this->ui->getWidget('related_articles_form');
            $index_form->visible = false;
        }

        return $rs;
    }
}
