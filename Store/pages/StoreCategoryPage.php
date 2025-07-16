<?php

/**
 * @copyright 2005-2016 silverorange
 */
class StoreCategoryPage extends StorePage
{
    protected $category;
    protected $products;
    protected $out_of_stock_products;

    // init phase

    public function isVisibleInRegion(StoreRegion $region)
    {
        $key = 'StoreCategoryPage.isVisibleInRegion.' . $region->id .
            '.' . $this->path;

        $category = $this->app->getCacheValue($key, 'product');
        if ($category !== false) {
            return $category !== null;
        }

        $category = null;

        if ($this->path !== null) {
            $path_entry = $this->path->getLast();
            if ($path_entry !== null) {
                $category_id = $path_entry->id;

                $sql = sprintf(
                    'select category from VisibleCategoryView
					where category = %s and (region = %s or region is null)',
                    $this->app->db->quote($category_id, 'integer'),
                    $this->app->db->quote($region->id, 'integer')
                );

                $category = SwatDB::queryOne($this->app->db, $sql);
            }
        }

        $this->app->addCacheValue($category, $key, 'product');

        return $category !== null;
    }

    protected function initInternal()
    {
        $category_id = $this->getSelectedCategoryId();
        $this->category = $this->queryCategory($category_id);

        if ($this->app->hasModule('StoreRecentModule')) {
            $this->app->getModule('StoreRecentModule')->setExclusionId(
                'categories',
                $category_id
            );
        }
    }

    protected function getSelectedCategoryId()
    {
        $category_id = null;

        if ($this->path !== null) {
            $path_entry = $this->path->getLast();
            if ($path_entry !== null) {
                $category_id = $path_entry->id;
            }
        }

        return $category_id;
    }

    // build phase

    public function build()
    {
        parent::build();

        $this->buildTitle();

        $this->layout->data->description =
            SwatString::minimizeEntities($this->category->description);

        $this->layout->startCapture('content');
        $this->buildBodytext();
        $this->layout->endCapture();

        if ($this->category->description === null) {
            $this->layout->data->meta_description =
                SwatString::minimizeEntities(SwatString::stripXHTMLTags(
                    SwatString::condense($this->category->bodytext, 400)
                ));
        } else {
            $this->layout->data->meta_description =
                SwatString::minimizeEntities($this->category->description);
        }

        $image = $this->category->image;
        if ($image !== null) {
            $this->layout->data->extra_headers .= sprintf(
                '<link rel="image_src" href="%s" />',
                $image->getUri('thumb', $this->app->getBaseHref())
            );
        }

        // subclasses may have loaded products already at this point, so avoid
        // querying all products again
        if ($this->products === null) {
            $this->products = $this->getProducts();
        }

        $this->buildPage();
    }

    protected function buildTitle()
    {
        $this->layout->data->title =
            SwatString::minimizeEntities($this->category->title);

        if ($this->category->html_title != '') {
            $this->layout->data->html_title =
                SwatString::minimizeEntities($this->category->html_title);
        }
    }

    protected function buildBodytext()
    {
        if ($this->category->bodytext != '') {
            printf(
                '<div class="store-category-bodytext">%s</div>',
                SwatString::toXHTML($this->category->bodytext)
            );
        }
    }

    protected function buildPage()
    {
        $last_entry = $this->path->getLast();

        $this->layout->startCapture('content');

        $this->displayRelatedContent($this->category);
        $this->displayFeaturedProducts($this->category);

        if ($this->isTwigPage()) {
            $this->displayTwigPage();
        } else {
            $this->displayPage();
        }

        $this->layout->endCapture();
    }

    protected function isTwigPage()
    {
        $twig_page = false;

        $last_entry = $this->path->getLast();
        if ($last_entry->twig) {
            $twig_page = true;
        }

        return $twig_page;
    }

    protected function querySubCategories(?StoreCategory $category = null)
    {
        // note: sub-categories are hard to memcache properly because of
        // the number of sub-dataobjects and the way they're loaded.
        // It's also a pretty fast query despite looking complicated.

        $sql = 'select Category.id, Category.title, Category.shortname,
				Category.description, Category.image,
				a.product_count as available_product_count,
				c.product_count, c.region as region_id, always_visible,
				i.item_count
			from Category
			left outer join CategoryAvailableProductCountByRegionCache as a
				on a.category = Category.id and a.region = %1$s
			left outer join CategoryVisibleProductCountByRegionCache as c
				on c.category = Category.id and c.region = %1$s
			left outer join CategoryVisibleItemCountByRegionCache as i
				on i.category = Category.id and i.region = %1$s
			where parent %2$s %3$s
			and id in
				(select Category from VisibleCategoryView
				where region = %4$s or region is null)
			order by displayorder, title';

        $category_id = ($category === null) ? null : $category->id;

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->app->getRegion()->id, 'integer'),
            SwatDB::equalityOperator($category_id),
            $this->app->db->quote($category_id, 'integer'),
            $this->app->db->quote($this->app->getRegion()->id, 'integer')
        );

        $wrapper_class = SwatDBClassMap::get(StoreCategoryWrapper::class);
        $sub_categories = SwatDB::query($this->app->db, $sql, $wrapper_class);
        $sub_categories->setRegion($this->app->getRegion());

        if (count($sub_categories) == 0) {
            return $sub_categories;
        }

        $sql = 'select * from Image where id in (%s)';
        $wrapper_class = SwatDBClassMap::get(StoreCategoryImageWrapper::class);
        $sub_categories->loadAllSubDataObjects(
            'image',
            $this->app->db,
            $sql,
            $wrapper_class
        );

        return $sub_categories;
    }

    protected function displaySubCategories(StoreCategoryWrapper $categories)
    {
        if (count($categories) == 0) {
            return;
        }

        echo '<ul class="store-category-list">';

        foreach ($categories as $category) {
            echo '<li class="store-category-tile">';
            $link = $this->source . '/' . $category->shortname;
            $category->displayAsTile($link);
            echo '</li>';
        }

        echo '</ul>';
    }

    protected function displayProducts($products, $path = null)
    {
        if ($path === null) {
            $path = $this->source;
        }

        echo '<ul class="store-product-list">';

        foreach ($products as $product) {
            echo '<li class="store-product-icon">';
            $link = $path . '/' . $product->shortname;
            $product->displayAsIcon($link);
            echo '</li>';
        }

        echo '</ul>';
    }

    protected function displayPage()
    {
        $sub_categories = $this->querySubCategories($this->category);

        if (count($this->products) == 1 && count($sub_categories) == 0) {
            $link = $this->source . '/' . $this->products->getFirst()->shortname;
            $this->app->relocate($link);
        } elseif (count($this->products) == 0 && count($sub_categories) == 1) {
            $link = $this->source . '/' . $sub_categories->getFirst()->shortname;
            $this->app->relocate($link);
        }

        if (count($sub_categories) > 0) {
            $this->displaySubCategories($sub_categories);
        }

        if (count($this->products) > 0) {
            $this->displayProducts($this->products);
        }
    }

    protected function displayTwigPage()
    {
        $products = $this->getProductsByCategory();

        $twig_has_products = isset($products[$this->category->id]);

        if ($twig_has_products) {
            $this->displayProducts($products[$this->category->id]);
        }

        $sub_categories = $this->category->getVisibleSubCategories(
            $this->app->getRegion()
        );

        if (count($sub_categories) == 0) {
            return;
        }
        if (!$twig_has_products && count($sub_categories) == 1) {
            $link = $this->source . '/' . $sub_categories->getFirst()->shortname;
            $this->app->relocate($link);
        }

        echo '<div class="category-twigs-wrapper">';

        foreach ($sub_categories as $sub_category) {
            if (array_key_exists($sub_category->id, $products) == true) {
                $twig_category_div = new SwatHtmlTag('div');
                $twig_category_div->class = 'category-twig';
                $twig_category_div->open();

                $title_header = new SwatHtmlTag('h3');
                $title_header->id = $sub_category->shortname;
                $title_header->class = 'category-twig-subtitle';
                $title_span = new SwatHtmlTag('span');
                $title_span->setContent($sub_category->title);

                $title_header->open();
                $title_span->display();
                $title_header->close();

                if ($sub_category->bodytext != '') {
                    $twig_bodytext = new SwatHtmlTag('div');
                    $twig_bodytext->class = 'category-twig-bodytext';
                    $twig_bodytext->setContent(
                        SwatString::toXHTML($sub_category->bodytext),
                        'text/xml'
                    );

                    $twig_bodytext->display();
                }

                $path = $this->source . '/' . $sub_category->shortname;
                $this->displayProducts($products[$sub_category->id], $path);

                $twig_category_div->close();
            }
        }

        $this->displayOutOfStockProductsAsTwig();
        echo '</div>';
    }

    protected function displayFeaturedProducts(StoreCategory $category)
    {
        // we only show featured products on pages with sub-categories
        if (count($this->querySubCategories($category)) == 0) {
            return;
        }

        $products = $this->getFeaturedProducts($category);
        if (count($products) > 0) {
            $div = new SwatHtmlTag('div');
            $div->id = 'featured_products';
            $div->open();

            $header_tag = new SwatHtmlTag('h4');
            $header_tag->setContent(Store::_('Featuring:'));
            $header_tag->display();

            $ul_tag = new SwatHtmlTag('ul');
            $ul_tag->class = 'store-product-list';
            $ul_tag->open();

            $li_tag = new SwatHtmlTag('li');
            $li_tag->class = 'store-product-text';

            foreach ($products as $product) {
                if ($this->isTwigPage() && !$product->isAvailableInRegion()) {
                    $this->out_of_stock_products[] = $product;
                } else {
                    $li_tag->open();
                    $path = $this->app->config->store->path . $product->path;
                    $product->displayAsText($path);
                    $li_tag->close();
                    echo ' ';
                }
            }

            $ul_tag->close();
            echo '<div class="clear"></div>';
            $div->close();
        }
    }

    protected function displayOutOfStockProductsAsTwig()
    {
        if (count($this->out_of_stock_products) > 0) {
            $twig_category_div = new SwatHtmlTag('div');
            $twig_category_div->class = 'category-twig';
            $twig_category_div->open();

            $title_header = new SwatHtmlTag('h3');
            $title_header->id = 'out-of-stock-twig';
            $title_header->class = 'category-twig-subtitle';
            $title_span = new SwatHtmlTag('span');
            $title_span->setContent(Store::_('Currently Out of Stock'));

            $title_header->open();
            $title_span->display();
            $title_header->close();

            echo '<ul class="store-product-list">';

            foreach ($this->out_of_stock_products as $product) {
                echo '<li class="store-product-icon">';
                $path = $this->app->config->store->path . $product->path;
                $product->displayAsIcon($path);
                echo '</li>';
            }

            echo '</ul>';

            $twig_category_div->close();
        }
    }

    protected function displayRelatedContent(StoreCategory $category)
    {
        $this->displayRelatedArticles($category);
    }

    protected function displayRelatedArticles(StoreCategory $category)
    {
        $category->setRegion($this->app->getRegion());
        if (count($category->related_articles) > 0) {
            $div = new SwatHtmlTag('div');
            $div->id = 'related_articles';
            $div->open();
            $this->displayRelatedArticlesTitle();

            $first = true;
            $anchor_tag = new SwatHtmlTag('a');
            foreach ($category->related_articles as $article) {
                if ($first) {
                    $first = false;
                } else {
                    echo ', ';
                }

                $anchor_tag->href = $article->path;
                $anchor_tag->setContent(
                    $this->getRelatedArticleTitle($article)
                );

                $anchor_tag->display();
            }

            $div->close();
        }
    }

    protected function displayRelatedArticlesTitle()
    {
        echo Store::_('Related Articles: ');
    }

    protected function getRelatedArticleTitle($article)
    {
        return $article->title;
    }

    protected function instantiateProductSearchEngine()
    {
        return new StoreProductSearchEngine($this->app);
    }

    protected function getProducts()
    {
        $engine = $this->instantiateProductSearchEngine();
        $engine->category = $this->category;
        $engine->addOrderByField('CategoryProductBinding.displayorder');
        $engine->addOrderByField('is_available desc');
        $engine->include_category_descendants = $this->isTwigPage();

        return $engine->search();
    }

    protected function getProductsByCategory()
    {
        $products = [];

        foreach ($this->products as $product) {
            if ($this->isTwigPage() && !$product->isAvailableInRegion()) {
                $this->out_of_stock_products[] = $product;
            } else {
                $category_id = $product->getInternalValue('primary_category');
                if (!array_key_exists($category_id, $products)) {
                    $products[$category_id] = [];
                }

                $products[$category_id][] = $product;
            }
        }

        return $products;
    }

    protected function getFeaturedProducts(StoreCategory $category)
    {
        $engine = $this->instantiateProductSearchEngine();
        $engine->featured_category = $category;
        $engine->addOrderByField('CategoryFeaturedProductBinding.displayorder');

        return $engine->search();
    }

    protected function buildNavBar()
    {
        if (!property_exists($this->layout, 'navbar')) {
            return;
        }

        if ($this->path !== null) {
            $link = 'store';
            foreach ($this->path as $path_entry) {
                $link .= '/' . $path_entry->shortname;
                $this->layout->navbar->createEntry($path_entry->title, $link);
            }
        }
    }
}
