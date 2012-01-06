<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatObject.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * @package   Store
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreProductFileGenerator extends SwatObject
{
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var StoreRegion
	 */
	protected $region;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new product file generator
	 *
	 * @param MDB2_Driver_Common $db
	 * @param SiteConfigModule $config
	 */
	public function __construct(SiteApplication $app)
	{
		$this->app = $app;

		if ($this->app->config->uri->cdn_base != '') {
			SiteImage::$cdn_base = $this->app->config->uri->cdn_base;
		}

		$this->region = $this->loadRegion();
	}

	// }}}
	// {{{ protected function loadRegion()

	protected function loadRegion()
	{
		$class_name = SwatDBClassMap::get('StoreRegion');
		$region = new $class_name();
		$region->setDatabase($this->app->db);

		// Note: this depends on us naming our contants the same on all sites.
		// if we ever don't, subclass this method. Also, we currently don't
		// upload anything but US Catalogs, if that changes this will need to
		// as well.
		$reflector   = new ReflectionObject($region);
		$us_constant = $reflector->getConstant('REGION_US');
		$region->load($us_constant);

		return $region;
	}

	// }}}
	// {{{ abstract public function generate()

	abstract public function generate();

	// }}}
	// {{{ protected function getBaseHref()

	protected function getBaseHref()
	{
		return $this->app->config->uri->absolute_base;
	}

	// }}}
	// {{{ abstract protected function getItems()

	/**
	 * @return StoreItemWrapper
	 */
	abstract protected function getItems();

	// }}}
}

?>
