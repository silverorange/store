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
	 * Some countries, such as ithe Republic of Ireland do not.
	 *
	 * @var boolean
	 */
	public $has_postal_code;

	// }}}
	// {{{ public function getRegionSelectTitle()

	public function getRegionSelectTitle()
	{
		switch ($this->id) {
		case 'DE':
			$select_title = Store::_('Select a Land …');
			break;
		case 'US':
		case 'MX':
			$select_title = Store::_('Select a State …');
			break;
		case 'CA':
		case 'AU':
			$select_title = Store::_('Select a Province …');
			break;
		case 'UK':
		default:
			$select_title = Store::_('Select a Region …');
			break;
		}

		return $select_title;
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
