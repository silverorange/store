<?php

/**
 * Resolves pages below the 'store' tag in the URL.
 *
 * @copyright 2005-2016 silverorange
 */
class StorePageFactory extends SitePageFactory
{
    /**
     * Creates a StoreArticlePageFactory.
     */
    public function __construct(SiteApplication $app)
    {
        parent::__construct($app);

        // set location to load Store page classes from
        $this->page_class_map['Store'] = 'Store/pages';
    }

    public function resolvePage($source, ?SiteLayout $layout = null): SiteAbstractPage
    {
        $layout = ($layout === null) ? $this->resolveLayout($source) : $layout;

        // if path is empty, load front page of store
        if ($source == '') {
            return $this->resolveFrontPage($source, $layout);
        }

        // if path ends with 'image', try to load product image page
        $regexp = '/\/image(\d+)?$/u';
        if (preg_match($regexp, $source, $regs)) {
            $source_exp = explode('/', $source);
            array_pop($source_exp);
            $source = implode('/', $source_exp);

            $product_info = $this->getProductInfo($source);
            $product_id = $product_info['product_id'];
            $category_id = $product_info['category_id'];

            if ($product_id === null) {
                throw new SiteNotFoundException();
            }

            $image_id = (isset($regs[1])) ? intval($regs[1]) : null;

            return $this->resolveProductImagePage(
                $source,
                $layout,
                $category_id,
                $product_id,
                $image_id
            );
        }

        // if path ends with 'landing', try to load product landing page
        $regexp = '/\/landing?$/u';
        if (preg_match($regexp, $source)) {
            $source_exp = explode('/', $source);
            array_pop($source_exp);
            $source = implode('/', $source_exp);

            $product_info = $this->getProductInfo($source);
            $product_id = $product_info['product_id'];
            $category_id = $product_info['category_id'];

            if ($product_id === null) {
                throw new SiteNotFoundException();
            }

            return $this->resolveProductLandingPage(
                $source,
                $layout,
                $category_id,
                $product_id
            );
        }

        $category_id = $this->getCategoryId($source);

        // if path is a valid category, load category page
        if ($category_id !== null) {
            return $this->resolveCategoryPage($source, $layout, $category_id);
        }

        $product_info = $this->getProductInfo($source);
        $product_id = $product_info['product_id'];
        $category_id = $product_info['category_id'];

        // if path is a valid product, load product page
        if ($product_id !== null) {
            return $this->resolveProductPage(
                $source,
                $layout,
                $category_id,
                $product_id
            );
        }

        // we wern't able to resolve a product or a category
        throw new SiteNotFoundException();
    }

    protected function resolveFrontPage($source, SiteLayout $layout)
    {
        throw new SiteNotFoundException();
    }

    // products

    protected function resolveProductPage(
        $source,
        SiteLayout $layout,
        $category_id,
        $product_id
    ) {
        $path = $this->getCategoryPath($category_id);

        $base_page = $this->instantiatePage($this->default_page_class, $layout);

        $page = $this->decorateProductPage($base_page);
        $page->setPath($path);
        $page->product_id = $product_id;

        if (!$page->isVisibleInRegion($this->app->getRegion())) {
            $page = $this->decorateProductNotVisiblePage($base_page);
            $page->setPath($path);
            $page->product_id = $product_id;
        }

        return $page;
    }

    protected function resolveProductLandingPage(
        $source,
        SiteLayout $layout,
        $category_id,
        $product_id
    ) {
        $page = $this->instantiatePage($this->default_page_class, $layout);
        $page = $this->decorateProductLandingPage($page);
        $page->setPath($this->getCategoryPath($category_id));
        $page->product_id = $product_id;

        return $page;
    }

    protected function resolveProductImagePage(
        $source,
        SiteLayout $layout,
        $category_id,
        $product_id,
        $image_id = null
    ) {
        $page = $this->instantiatePage($this->default_page_class, $layout);
        $page = $this->decorateProductImagePage($page);
        $page->setPath($this->getCategoryPath($category_id));
        $page->product_id = $product_id;
        $page->image_id = $image_id;

        return $page;
    }

    protected function decorateProductPage(SiteAbstractPage $page)
    {
        return $this->decorate($page, 'StoreProductPage');
    }

    protected function decorateProductNotVisiblePage(SiteAbstractPage $page)
    {
        return $this->decorate($page, 'StoreProductNotVisiblePage');
    }

    protected function decorateProductImagePage(SiteAbstractPage $page)
    {
        return $this->decorate($page, 'StoreProductImagePage');
    }

    protected function decorateProductLandingPage(SiteAbstractPage $page)
    {
        return $page;
    }

    /**
     * @param string $source
     */
    protected function getProductInfo($source)
    {
        if (isset($this->app->memcache)) {
            $key = 'StorePageFactory.getProductInfo' . $source . '.' .
                $this->app->getRegion()->id;

            $data = $this->app->memcache->getNs('product', $key);
            if (is_array($data)) {
                return $data;
            }
        }

        $source_exp = explode('/', $source);
        $db = $this->app->db;
        $region_id = $this->app->getRegion()->id;
        $product_id = null;
        $category_id = null;

        if (count($source_exp) > 1) {
            $product_shortname = array_pop($source_exp);
            $category_id = $this->getCategoryId(implode('/', $source_exp));

            if ($category_id !== null) {
                $sql = 'select id from Product where shortname = %s
					and id in
					(select product from CategoryProductBinding
					where category = %s)
					and id in
					(select product from VisibleProductCache where region = %s)';

                $sql = sprintf(
                    $sql,
                    $db->quote($product_shortname, 'text'),
                    $db->quote($category_id, 'integer'),
                    $db->quote($region_id, 'integer')
                );

                $product_id = SwatDB::queryOne($db, $sql);
            }
        } else {
            /*
             * Last chance: look for uncategorized products that are visible
             * due to the site-specific implementation of VisibleProductView.
             */
            $product_shortname = array_shift($source_exp);

            $sql = 'select id from Product where shortname = %s
				and id not in
				(select product from CategoryProductBinding)
				and id in
				(select product from VisibleProductCache where region = %s)';

            $sql = sprintf(
                $sql,
                $db->quote($product_shortname, 'text'),
                $db->quote($region_id, 'integer')
            );

            $product_id = SwatDB::queryOne($db, $sql);
        }

        $data = [
            'product_id'  => $product_id,
            'category_id' => $category_id,
        ];

        if (isset($this->app->memcache)) {
            $this->app->memcache->setNs('product', $key, $data);
        }

        return $data;
    }

    // categories

    protected function resolveCategoryPage(
        $source,
        SiteLayout $layout,
        $category_id
    ) {
        $path = $this->getCategoryPath($category_id);

        $base_page = $this->instantiatePage($this->default_page_class, $layout);

        $page = $this->decorateCategoryPage($base_page);
        $page->setPath($path);
        $page->category_id = $category_id;

        if (!$page->isVisibleInRegion($this->app->getRegion())) {
            $page = $this->decorateCategoryNotVisiblePage($base_page);
            $page->setPath($path);
            $page->category_id = $category_id;
        }

        return $page;
    }

    protected function decorateCategoryPage(SiteAbstractPage $page)
    {
        return $this->decorate($page, 'StoreCategoryPage');
    }

    protected function decorateCategoryNotVisiblePage(SiteAbstractPage $page)
    {
        return $this->decorate($page, 'StoreCategoryNotVisiblePage');
    }

    /**
     * @param string $path
     */
    protected function getCategoryId($path)
    {
        // don't try to resolve categories that are deeper than the max depth
        if (mb_substr_count($path, '/') >= StoreCategory::MAX_DEPTH) {
            throw new SitePathTooLongException(
                sprintf('Category path is too long: ‘%s’', $path)
            );
        }

        // don't try to find categories with invalid UTF-8 in the path
        if (!SwatString::validateUtf8($path)) {
            throw new SitePathInvalidUtf8Exception(
                sprintf(
                    'Category path is not valid UTF-8: "%s"',
                    SwatString::escapeBinary($path)
                ),
                0,
                $path
            );
        }

        // don't try to find catrgories with more than 254 characters in the
        // path
        if (mb_strlen($path) > 254) {
            throw new SitePathTooLongException(
                sprintf('Category path is too long: ‘%s’', $path)
            );
        }

        return SwatDB::executeStoredProcOne(
            $this->app->db,
            'findCategory',
            [$this->app->db->quote($path, 'text')]
        );
    }

    protected function getCategoryPath($category_id = null)
    {
        return new StoreCategoryPath($this->app, $category_id);
    }
}
