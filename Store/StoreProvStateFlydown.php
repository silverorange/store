<?php

require_once 'Swat/SwatFlydown.php';
require_once 'Store.php';

/**
 * A widget for selecting a provstate
 *
 * This widget validates that the selected provstate is valid in a given
 * county.  To validate a provstate, the widget needs to know the country and
 * have a reference to the database. Set the
 * {@link StoreProvStateFlydown::$country} property to a known ISO-3611 code
 * and call {@link StoreProvStateFlydown::setDatabase()}.
 *
 * @package   Store
 * @copyright 2008 silverorange
 */
class StoreProvStateFlydown extends SwatFlydown
{
	// {{{ public function process()

	/**
	 * Processes this postal code entry widget
	 *
	 * The postal code is validated and formatted correctly.
	 */
	public function process()
	{
		parent::process();

		if ($this->db !== null && $this->country !== null &&
			$this->value !== null)
				$this->validate();
	}

	// }}}
	// {{{ public properties

	/**
	 * The country to validate the provstate in
	 *
	 * This should be a valid ISO-3611 two-digit country code.
	 *
	 * @var string
	 */
	public $country;

	// }}}
	// {{{ protected properties

	/**
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	// }}}
	// {{{ public function setDatabase()

	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		// validate provstate by country
		$sql = sprintf('select count(id) from ProvState
			where id = %s and country = %s',
			$this->db->quote($this->value, 'integer'),
			$this->db->quote($this->country, 'text'));

		$count = SwatDB::queryOne($this->db, $sql);

		if ($count == 0) {
			$country_title = SwatDB::queryOne($this->db,
				sprintf('select title from Country where id = %s',
				$this->db->quote($this->country)));

			if ($country_title === null) {
				$message_content = Store::_('The selected %s is '.
					'not a province or state of the selected country.');
			} else {
				$message_content = sprintf(Store::_('The selected '.
					'%%s is not a province or state of the selected '.
					'country %s%s%s.'),
					'<strong>', $country_title, '</strong>');
			}

			$message = new SwatMessage($message_content,
				SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$this->addMessage($message);
		}
	}

	// }}}
}

?>
