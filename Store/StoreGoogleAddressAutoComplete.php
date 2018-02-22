<?php

/**
 * Control to use Google Address Autocomplete
 *
 * @package   Store
 * @copyright 2018 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreGoogleAddressAutoComplete extends SwatControl
{
	// {{{ protected properties

	/**
	 * @var SiteWebApplication
	 */
	protected $app;

	protected static $run_once = true;

	// }}}
	// {{{ public function setApplication()

	public function setApplication(SiteWebApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		SwatControl::display();

		if (!self::$run_once) {
			return;
		}

		self::$run_once = false;

		$address_config = $this->app->config->google_address_auto_complete;
		if ($address_config->enabled && $address_config->api_key != '') {
			$script = new SwatHtmlTag('script');
			$script->type = 'text/javascript';
			$script->src =  sprintf(
				'https://maps.googleapis.com/maps/api/js'.
				'?key=%s&libraries=places',
				urlencode($address_config->api_key)
			);

			$script->open();
			$script->close();

			Swat::displayInlineJavaScript($this->getInlineJavaScript());

			$this->addJavascript(
				'packages/store/javascript/'.
				'store-google-address-auto-complete.js'
			);
		}
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$values = [];
		foreach ($this->getProvStates() as $provstate) {
			$values[] = [
				'id' => $provstate->id,
				'country' => $provstate->country,
				'code' => $provstate->abbreviation,
			];
		}

		return sprintf(
			'StoreGoogleAddressAutoComplete.prov_states = %s;',
			json_encode($values)
		);
	}

	// }}}
	// {{{ protected function getProvStates()

	protected function getProvStates()
	{
		$where_clause = sprintf(
			'id in (
				select provstate from RegionBillingProvStateBinding
				where region = %s)',
			$this->app->db->quote($this->app->getRegion()->id, 'integer')
		);

		$sql = sprintf(
			'select id, country, abbreviation from ProvState where %s',
			$where_clause
		);

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
}

?>
