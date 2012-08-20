<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A country data object
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCountry extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier of this country
	 *
	 * @var string
	 */
	public $id;

	/**
	 * User visible title of this country
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Whether or not to show this country on the front-end
	 *
	 * @var boolean
	 */
	public $visible;

	/**
	 * Whether or not this country has a postal code system
	 *
	 * Some countries, such as the Republic of Ireland do not.
	 *
	 * @var boolean
	 */
	public $has_postal_code;

	// }}}
	// {{{ public function getRegionTitle()

	public function getRegionTitle()
	{
		switch ($this->id) {
		case 'AU':
		case 'MY':
			$title = Store::_('State/Territory');
			break;
		case 'AR':
		case 'BE':
		case 'CN':
		case 'CR':
		case 'CZ':
		case 'ES':
		case 'ID':
		case 'IT':
		case 'KP':
		case 'NZ':
		case 'UY':
		case 'VE':
			$title = Store::_('Province');
			break;
		case 'AT':
		case 'BR':
		case 'IN':
		case 'MX':
		case 'OM':
		case 'US':
			$title = Store::_('State');
			break;
		case 'CA':
			$title = Store::_('Province/Territory');
			break;
		case 'CL':
			$title = Store::_('Municipality');
			break;
		case 'DE':
			$title = Store::_('Land');
			break;
		case 'DK':
		case 'FI':
		case 'GL':
			$title = Store::_('Postal District');
			break;
		case 'ES':
		case 'GB':
		case 'IE':
			$title = Store::_('County');
			break;
		case 'FR':
		case 'ZA':
			$title = Store::_('Locality');
			break;
		case 'IL':
		case 'PK':
			$title = Store::_('District');
			break;
		case 'LV':
			$title = Store::_('Amalgameted Municipality');
			break;
		case 'PT':
			$title = Store::_('Territory');
			break;
		case 'RO':
			$title = Store::_('County/Sector');
			break;
		case 'TW':
			$title = Store::_('Island');
			break;
		default:
			$title = Store::_('Region');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public function getRegionRequired()

	public function getRegionRequired()
	{
		switch ($this->id) {
		case 'AU':
		case 'BR':
		case 'CA':
		case 'CN':
		case 'CR':
		case 'IT':
		case 'JP':
		case 'KP':
		case 'MX':
		case 'MY':
		case 'RO':
		case 'RU':
		case 'US':
			$required = true;
			break;
		default:
			$required = false;
			break;
		}

		return $required;
	}

	// }}}
	// {{{ public function getRegionVisible()

	public function getRegionVisible()
	{
		switch ($this->id) {
		case 'EE':
		case 'IL':
		case 'DE':
		case 'FR':
		case 'BE':
		case 'NZ':
		case 'CR':
		case 'CZ':
		case 'FJ':
		case 'IS':
		case 'LU':
		case 'NL':
		case 'NO':
		case 'PL':
		case 'SG':
		case 'SE':
		case 'AE':
		case 'AT':
		case 'OM':
			$visible = false;
			break;
		default:
			$visible = true;
			break;
		}

		return $visible;
	}

	// }}}
	// {{{ public function getRegionSelectTitle()

	public function getRegionSelectTitle()
	{
		switch ($this->id) {
		case 'AU':
		case 'MY':
			$title = Store::_('Select a State/Territory …');
			break;
		case 'IT':
			$title = Store::_('Select a Province …');
			break;
		case 'BR':
		case 'US':
			$title = Store::_('Select a State …');
			break;
		case 'CA':
			$title = Store::_('Select a Province/Territory …');
			break;
		default:
			$title = Store::_('Select a Region …');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public function getPostalCodeTitle()

	public function getPostalCodeTitle()
	{
		switch ($this->id) {
		case 'AU':
		case 'CN':
		case 'GB':
		case 'IE':
		case 'NZ':
		case 'SG':
			$title = Store::_('Postcode');
			break;
		case 'PK':
			$title = Store::_('Post Code');
			break;
		case 'US':
			$title = Store::_('ZIP Code');
			break;
		default:
			$title = Store::_('Postal Code');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public function getPostalCodeRequired()

	public function getPostalCodeRequired()
	{
		return $this->has_postal_code;
	}

	// }}}
	// {{{ public function getCityTitle()

	public function getCityTitle()
	{
		switch ($this->id) {
		case 'GB':
			$title = Store::_('Post Town');
			break;
		default:
			$title = Store::_('City');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public function getCityRequired()

	public function getCityRequired()
	{
		switch ($this->id) {
		default:
			$required = true;
			break;
		}

		return $required;
	}

	// }}}
	// {{{ public static function getTitleById()

	/**
	 * Get the title of the country from an id.
	 *
	 * @param MDB2_Driver_Common $db the database connection.
	 * @param string $id the ISO-3166-1 alpha-2 code for the country of the
	 *                    province/state to load.
	 *
	 * @return string the title of the country, or null if not found.
	 */
	public static function getTitleById(MDB2_Driver_Common $db, $id)
	{
		$sql = sprintf('select title from Country where id = %s',
			$db->quote($id, 'text'));

		$title = SwatDB::queryOne($db, $sql);

		return $title;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Country';
		$this->id_field = 'text:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadProvStates()

	protected function loadProvStates()
	{
		$sql = sprintf(
			'select * from ProvState where country = %s',
			$this->db->quote($this->id, 'text')
		);

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProvStateWrapper'));
	}

	// }}}
}

?>
