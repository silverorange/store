<?php

require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Generates Google AdWords purchase conversion tracking code for an order
 *
 * @package   Store
 * @copyright 2008-2010 silverorange
 */
class StoreAdWordsTracker
{
	// {{{ protected properties

	/**
	 * @var StoreOrder
	 */
	protected $order;

	/**
	 * @var integer
	 */
	protected $conversion_id;

	// }}}
	// {{{ public function __construct()

	public function __construct(StoreOrder $order, $conversion_id)
	{
		$this->order         = $order;
		$this->conversion_id = $conversion_id;
	}

	// }}}
	// {{{ public function getInlineXhtml()

	public function getInlineXhtml()
	{
		$total         = $this->order->total;
		$conversion_id = $this->conversion_id;

		// {{{ returned HTML
		return <<<HTML
<div class="google-adwords-tracking">
<script type="text/javascript">// <![CDATA[
var google_conversion_id       = {$conversion_id};
var google_conversion_language = 'en_US';
var google_conversion_format   = '1';
var google_conversion_color    = 'FFFFFF';
var google_conversion_value    = {$total};
var google_conversion_label    = 'purchase';
// ]]></script>
<script type="text/javascript" src="https://www.googleadservices.com/pagead/conversion.js"></script>
<noscript>
<img width="1" height="1" alt="" src="https://www.googleadservices.com/pagead/conversion/{$conversion_id}/imp.gif?value={$total}&amp;label=purchase&amp;script=0" />
</noscript>
</div>
HTML;
		// }}}
	}

	// }}}
}

?>
