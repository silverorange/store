<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * A dataobject used to store MailChimp order information
 *
 * The order information in this object is sent to MailChimp using the
 * {@link StoreMailChimpOrderUpdater} command line application.
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreMailChimpModule
 * @see       StoreMailChimpOrderUpdater
 */
class StoreMailChimpOrder extends SwatDBDataObject
{
	// {{{ constants

	/**
	 * The maximum number of attempts made to send an order to MailChimp
	 *
	 * @var integer
	 */
	const MAX_SEND_ATTEMPTS = 3;

	// }}}
	// {{{ public properties

	/**
	 * A unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The contents of a user's 'mc_eid' cookie
	 *
	 * @var string
	 */
	public $email_id;

	/**
	 * The contents of a user's 'mc_cid' cookie, this is optional
	 *
	 * @var string
	 */
	public $campaign_id;

	/**
	 * The number of times we've tried to send this to MailChimp
	 *
	 * @var integer
	 */
	public $send_attempts;

	/**
	 * The date we stopped trying to send this order to MailChimp
	 *
	 * @var SwatDate
	 */
	public $error_date;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('ordernum',
			SwatDBClassMap::get('StoreOrder'));

		$this->registerDateProperty('error_date');

		$this->table = 'MailChimpOrderQueue';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
