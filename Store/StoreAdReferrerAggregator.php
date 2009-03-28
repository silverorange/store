<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Store/StoreCommandLineConfigModule.php';
require_once 'Store/Store.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Class for aggregating the total number of referrers per ad.
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdReferrerAggregator extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();
		$this->aggregate();
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'StoreCommandLineConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
		parent::addConfigDefinitions($config);
		$config->addDefinitions(Store::getConfigDefinitions());
	}

	// }}}
	// {{{ protected function aggregate()

	protected function aggregate()
	{
		$this->debug(Store::_('Querying unaggregated referrers'."\n"));

		$sql = sprintf('select count(id) as num_referrers, ad,
				max(id) as max_id
			from AdReferrer
			where aggregated = %s group by ad',
			$this->db->quote(false, 'boolean'));

		$referrers = SwatDB::query($this->db, $sql);
		$max_id = 0;

		$this->debug(Store::_('Updating ad counts'));
		foreach ($referrers as $referrer) {
			$sql = sprintf('update Ad set
				total_referrers = total_referrers + %s where id = %s',
				$this->db->quote($referrer->num_referrers),
				$this->db->quote($referrer->ad));

			SwatDB::exec($this->db, $sql);

			$max_id = max($max_id, $referrer->max_id);
		}

		$this->debug(Store::_('Setting referrers as aggregated'."\n"));
		$sql = sprintf('update AdReferrer set aggregated = %s
			where aggregated = %s and id <= %s',
			$this->db->quote(true, 'boolean'),
			$this->db->quote(false, 'boolean'),
			$this->db->quote($max_id, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->debug(Store::_('done')."\n\n");
	}

	// }}}
}

?>
