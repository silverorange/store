<?php

require_once 'Site/admin/components/Ad/Edit.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Edit page for Ads
 *
 * Store also saves the ad locale-bindings.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdEdit extends SiteAdEdit
{
	// process phase
	// {{{ protected function saveAd()

	protected function saveAd()
	{
		parent::saveAd();
		$this->saveAdLocaleBinding();
	}

	// }}}
	// {{{ protected function saveAdLocaleBinding()

	protected function saveAdLocaleBinding()
	{
		// create ad locale bindings
		$sql = sprintf('insert into AdLocaleBinding (ad, locale)
			select %s, Locale.id as locale from Locale',
			$this->app->db->quote($this->ad->id, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
}

?>
