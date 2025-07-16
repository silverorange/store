<?php

/**
 * @copyright 2007-2016 silverorange
 */
class StoreSearchPanelServer extends SiteXMLRPCServer
{
    /**
     * Returns the XHTML required to display the search panel for the
     * advanced search.
     *
     * @param string $query_string the query string containg the state of the
     *                             search panel
     * @param string $uri          the URI of the page making the request
     *
     * @return string the XHTML required to display the search panel
     */
    public function getContent($query_string, $uri)
    {
        $query_string_exp = explode('&', $query_string);
        $args = [];
        foreach ($query_string_exp as $parameter) {
            if (mb_strpos($parameter, '=')) {
                [$key, $value] = explode('=', $parameter, 2);
            } else {
                $key = $parameter;
                $value = null;
            }

            $key = urldecode($key);
            $value = urldecode($value);

            $regs = [];
            if (preg_match('/^(.+)\[(.*)\]$/', $key, $regs)) {
                $key = $regs[1];
                $array_key = ($regs[2] == '') ? null : $regs[2];
                if (!isset($args[$key])) {
                    $args[$key] = [];
                }

                if ($array_key === null) {
                    $args[$key][] = $value;
                } else {
                    $args[$key][$array_key] = $value;
                }
            } else {
                $args[$key] = $value;
            }
        }

        foreach ($args as $key => $value) {
            $_GET[$key] = $value;
        }

        // parse uri components from special seach pages into GET vars
        $uri = mb_substr($uri, mb_strlen($this->app->getBaseHref()));
        $uri_exp = explode('/', $uri);
        if (count($uri_exp) == 3) {
            $key = $uri_exp[1];
            $value = $uri_exp[2];
            $_GET[$key] = $value;
        }

        ob_start();

        $panel = $this->getPanel();
        $panel->init();
        $panel->process();
        $this->setValues($panel);
        $panel->display();

        return ob_get_clean();
    }

    protected function getPanel()
    {
        return new StoreSearchPanel(
            $this->app->db,
            $this->app->getRegion()
        );
    }

    protected function setValues(StoreSearchPanel $panel)
    {
        $panel->setPriceRange($this->getPriceRange());
        $panel->setCategory($this->getCategory());
    }

    /**
     * @xmlrpc.hidden
     */
    protected function getPriceRange()
    {
        $range = null;

        if (isset($_GET['price'])) {
            $range = new StorePriceRange($_GET['price']);
            $range->normalize();
        }

        return $range;
    }

    /**
     * @xmlrpc.hidden
     */
    protected function getCategory()
    {
        $category = null;

        if (isset($_GET['category'])) {
            $sql = 'select id, shortname, title from Category
				where id = findCategory(%s) and id in
					(select category from VisibleCategoryView
					where region = %s or region is null)';

            $sql = sprintf(
                $sql,
                $this->app->db->quote($_GET['category'], 'text'),
                $this->app->db->quote($this->app->getRegion()->id, 'integer')
            );

            $category = SwatDB::query(
                $this->app->db,
                $sql,
                'StoreCategoryWrapper'
            )->getFirst();
        }

        return $category;
    }
}
