<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';

/**
 * A recordset wrapper class for StoreOrderPaymentMethod objects
 *
 * @package   Store
 * @copyright 2005-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderPaymentMethodWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function getByPayPalToken()

	public function getByPayPalToken($token)
	{
		$payment_method = null;

		foreach ($this as $method) {
			if ($method->getPayPalToken() == $token) {
				$payment_method = $method;
				break;
			}
		}

		return $payment_method;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'id';
		$this->row_wrapper_class =
			SwatDBClassMap::get('StoreOrderPaymentMethod');
	}

	// }}}
}

?>
