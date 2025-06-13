<?php

/**
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreProductFileGenerator extends SwatObject
{
    /**
     * @var SiteApplication
     */
    protected $app;

    /**
     * @var StoreRegion
     */
    protected $region;

    /**
     * Creates a new product file generator.
     */
    public function __construct(SiteApplication $app)
    {
        $this->app = $app;

        if ($this->app->config->uri->cdn_base != '') {
            SiteImage::$cdn_base = $this->app->config->uri->cdn_base;
        }

        $this->region = $this->loadRegion();
    }

    abstract public function generate();

    /**
     * @return StoreItemWrapper
     */
    abstract protected function getItems();

    protected function loadRegion()
    {
        $class_name = SwatDBClassMap::get('StoreRegion');
        $region = new $class_name();
        $region->setDatabase($this->app->db);

        // Note: this depends on us naming our contants the same on all sites.
        // if we ever don't, subclass this method. Also, we currently don't
        // upload anything but US Catalogs, if that changes this will need to
        // as well.
        $reflector = new ReflectionObject($region);
        $us_constant = $reflector->getConstant('REGION_US');
        $region->load($us_constant);

        return $region;
    }

    protected function getBaseHref()
    {
        return $this->app->config->uri->absolute_base;
    }
}
