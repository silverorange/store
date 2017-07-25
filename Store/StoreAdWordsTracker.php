<?php

/**
 * Generates Google AdWords purchase conversion tracking code for an order
 *
 * @package   Store
 * @copyright 2008-2016 silverorange
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
		$conversion_label) {
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
		$currency         = 'USD';

		$js_conversion_id = (int)$conversion_id;
		$js_conversion_label = SwatString::quoteJavaScriptString(
			$conversion_label
		);
		$js_value = (float)$total;
		$js_currency = SwatString::quoteJavaScriptString($currency);

		$xml_conversion_id = SwatString::minimizeEntities($conversion_id);
		$xml_conversion_label = SwatString::minimizeEntities(
			$conversion_label
		);
		$xml_value = SwatString::minimizeEntities($total);
		$xml_currency = SwatString::minimizeEntities($currency);

		// {{{ returned HTML

		// Note: Format 3 is hiding the Google Site Stats box
		// @codingStandardsIgnoreStart
		$html = <<<HTML
<div class="google-adwords-tracking">
  <script>
    /* <![CDATA[ */
    var google_conversion_id = %s;
    var google_conversion_language = "en";
    var google_conversion_format = "3";
    var google_conversion_color = "ffffff";
    var google_conversion_label = %s;
    var google_conversion_value = %s;
    var google_conversion_currency = %s;
    var google_remarketing_only = false;
    /* ]]> */
  </script>
  <script src="//www.googleadservices.com/pagead/conversion.js"></script>
  <noscript>
    <div style="display:inline;">
      <img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/%s/?value=%s&amp;currency_code=%s&amp;label=%s&amp;guid=ON&amp;script=0" />
    </div>
  </noscript>
</div>
HTML;
		// @codingStandardsIgnoreEnd
		// }}}

		return sprintf(
			$html,
			$js_conversion_id,
			$js_conversion_label,
			$js_value,
			$js_currency,
			$xml_conversion_id,
			$xml_value,
			$xml_currency,
			$xml_conversion_label
		);
	}

	// }}}
}

?>
