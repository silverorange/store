<?php

/**
 * A province/state data object
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 */
class StoreProvState extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Tax message
	 *
	 * If this province or state requires special tax procedures, this note
	 * will be displayed to customers shipping to it.
	 *
	 * @var string
	 */
	public $tax_message;

	// }}}
	// {{{ protected properties

	/**
	 * Unique identifier of this province or state
	 *
	 * @var integer
	 */
	protected $id;

	/**
	 * User visible title of this province or state
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * A two letter abbreviation used to identify this province of state
	 *
	 * This is also used for displaying addresses.
	 *
	 * @var string
	 */
	protected $abbreviation;

	// }}}
	// {{{ public static function getAbbreviationById()

	/**
	 * Get the abbreviation of the provstate from an id.
	 *
	 * @param MDB2_Driver_Common $db the database connection.
	 * @param integer $id the id of the province.
	 *
	 * @return string the abbreviation of the provstate, or null if not found.
	 */
	public static function getAbbreviationById(MDB2_Driver_Common $db, $id)
	{
		$sql = sprintf('select abbreviation from ProvState where id = %s',
			$db->quote($id, 'integer'));

		$abbreviation = SwatDB::queryOne($db, $sql);

		return $abbreviation;
	}

	// }}}
	// {{{ public static function getTitleById()

	/**
	 * Get the title of the provstate from an id.
	 *
	 * @param MDB2_Driver_Common $db the database connection.
	 * @param integer $id the id of the province.
	 *
	 * @return string the title of the provstate, or null if not found.
	 */
	public static function getTitleById(MDB2_Driver_Common $db, $id)
	{
		$sql = sprintf('select title from ProvState where id = %s',
			$db->quote($id, 'integer'));

		$title = SwatDB::queryOne($db, $sql);

		return $title;
	}

	// }}}
	// {{{ public function loadFromAbbreviation()

	/**
	 * Loads this province/state from an abbreviation and country code
	 *
	 * @param string $abbreviation the abbreviation of this province/state.
	 * @param string $country the ISO-3166-1 alpha-2 code for the country of
	 *                         the province/state to load.
	 *
	 * @return boolean true if this province/state was loaded and false if it
	 *                  was not.
	 */
	public function loadFromAbbreviation($abbreviation, $country)
	{
		$this->checkDB();

		$row = null;
		$loaded = false;

		if ($this->table !== null) {
			$sql = sprintf('select * from ProvState
				where abbreviation = %s and country = %s',
				$this->db->quote($abbreviation, 'text'),
				$this->db->quote($country, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ public function loadFromTitle()

	/**
	 * Loads this province/state from a title and country code
	 *
	 * @param string $title the full title of this province/state
	 * @param string $country the ISO-3166-1 alpha-2 code for the country of
	 *                         the province/state to load.
	 *
	 * @return boolean true if this province/state was loaded and false if it
	 *                  was not.
	 */
	public function loadFromTitle($title, $country)
	{
		$this->checkDB();

		$row = null;
		$loaded = false;

		if ($this->table !== null) {
			$sql = sprintf('select * from ProvState
				where title = %s and country = %s',
				$this->db->quote($title, 'text'),
				$this->db->quote($country, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ProvState';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('country',
			SwatDBClassMap::get('StoreCountry'));
	}

	// }}}
	// {{{ protected function getProtectedPropertyList()

	protected function getProtectedPropertyList()
	{
		return array_merge(
			parent::getProtectedPropertyList(),
			array(
				'id' => array(
					'get' => 'getId',
					'set' => 'setId',
				),
				'title' => array(
					'get' => 'getTitle',
					'set' => 'setTitle',
				),
				'abbreviation' => array(
					'get' => 'getAbbreviation',
					'set' => 'setAbbreviation',
				),
			)
		);
	}

	// }}}

	// getters
	// {{{ public function getId()

	public function getId()
	{
		return $this->id;
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		return $this->title;
	}

	// }}}
	// {{{ public function getAbbreviation()

	public function getAbbreviation()
	{
		return $this->abbreviation;
	}

	// }}}

	// setters
	// {{{ public function setId()

	public function setId($id)
	{
		$this->id = $id;
	}

	// }}}
	// {{{ public function setTitle()

	public function setTitle($title)
	{
		$this->title = $title;
	}

	// }}}
	// {{{ public function setAbbreviation()

	public function setAbbreviation($abbreviation)
	{
		$this->abbreviation = $abbreviation;
	}

	// }}}
}

?>
