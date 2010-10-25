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

	/**
	 * @var string
	 */
	protected $conversion_label;

	// }}}
	// {{{ public function __construct()

	public function __construct(StoreOrder $order, $conversion_id,
		$conversion_label)
	{
		$this->order            = $order;
		$this->conversion_id    = $conversion_id;
		$this->conversion_label = $conversion_label;
	}

	// }}}
	// {{{ public function getInlineXhtml()

	public function getInlineXhtml()
	{
		$total            = $this->order->total;
		$conversion_id    = $this->conversion_id;
		$conversion_label = $this->conversion_label;

		// {{{ returned HTML

		// Note: Format 3 is hiding the Google Site Stats box
		return <<<HTML
<div class="google-adwords-tracking">
<script type="text/javascript">// <![CDATA[
var google_conversion_id       = {$conversion_id};
var google_conversion_language = 'en_US';
var google_conversion_format   = '3';
var google_conversion_color    = 'FFFFFF';
var google_conversion_value    = {$total};
var google_conversion_label    = '{$conversion_label}';
// ]]></script>
<script type="text/javascript" src="https://www.googleadservices.com/pagead/conversion.js"></script>
<noscript>
<img width="1" height="1" alt="" src="https://www.googleadservices.com/pagead/conversion/{$conversion_id}/imp.gif?value={$total}&amp;label={$conversion_label}&amp;script=0" />
</noscript>
</div>
HTML;

		// }}}
	}

	// }}}
}

?>
