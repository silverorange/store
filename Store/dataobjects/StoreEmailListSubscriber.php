<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreLocale.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreEmailListSubscriber extends StoreDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $email;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'EmailListSubscriber';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('locale', 'StoreLocale');
	}

	// }}}
	// {{{ public static function removeByEmail()

	public static function removeByEmail($db, $email)
	{
		$sql = 'delete from EmailListSubscriber where email = %s';
		$sql = sprintf($sql, $db->quote($email, 'text'));

		SwatDB::query($db, $sql);
	}
	// }}}
}

?>
