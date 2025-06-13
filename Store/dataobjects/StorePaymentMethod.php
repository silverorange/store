<?php

/**
 * A payment method for an ecommerce web application.
 *
 * Payment methods are usually tied to {@link StoreAccount} objects or
 * {@link StoreOrder} objects.
 *
 * A payment method represents a way to pay for a purchase. A payment method
 * stores the type of payment (VISA, MC, COD) as well as necessary payment
 * details such as name, card number and expiry date.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StorePaymentType
 * @see       StoreCardType
 */
abstract class StorePaymentMethod extends SwatDBDataObject
{
    /**
     * Payment method identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Optional surcharge.
     *
     * @var float
     */
    public $surcharge;

    /**
     * Full name on the card.
     *
     * @var string
     */
    public $card_fullname;

    /**
     * Last X digits of the card.
     *
     * This value is stored unencrypted and is displayed to the customer to
     * allow the customer to identify his or her cards.  Field length in the
     * database is 6, but stored length is dependent on card type.
     *
     * @var string
     */
    public $card_number_preview;

    /**
     * The expiry date of the card.
     *
     * @var SwatDate
     */
    public $card_expiry;

    /**
     * The inception date of the card.
     *
     * This is required for some debit cards.
     *
     * @var SwatDate
     */
    public $card_inception;

    /**
     * The issue number for Switch and Solo debit cards.
     *
     * This is a 1 or 2 character string containing the issue number exactly as
     * it appears on the card. Note: This is a string not an integer. An issue
     * number of '04' is different than an issue number of '4' and both numbers
     * are valid issue numbers.
     *
     * @var string
     */
    public $card_issue_number;

    /**
     * Identifier of the payer.
     *
     * Used for online payment systems like PayPal and Bill Me Later where
     * card information is not transmitted between the merchant and the
     * payment provider.
     *
     * @var string
     */
    public $payer_id;

    /**
     * Email address of the payer.
     *
     * Used for online payment systems like PayPal and Bill Me Later where
     * card information is not transmitted between the merchant and the
     * payment provider.
     *
     * @var string
     */
    public $payer_email;

    /**
     * The unencrypted card number of this payment method.
     *
     * Note: This should NEVER be saved. Not ever.
     *
     * @var string
     *
     * @see StorePaymentMethod::getUnencryptedCardNumber()
     */
    protected $unencrypted_card_number = '';

    /**
     * When displaying the payment method, defines which parts to show.
     *
     * @var array
     *
     * @see StorePaymentMethod::showCardNumber()
     * @see StorePaymentMethod::showCardFullname()
     * @see StorePaymentMethod::showCardExpiry()
     */
    protected $display_parts = [
        'card_number'   => true,
        'card_fullname' => true,
        'card_expiry'   => true,
    ];

    public function showCardNumber($display = true)
    {
        $this->display_parts['card_number'] = $display;
    }

    public function showCardFullname($display = true)
    {
        $this->display_parts['card_fullname'] = $display;
    }

    public function showCardExpiry($display = true)
    {
        $this->display_parts['card_expiry'] = $display;
    }

    /**
     * Sets the card number of this payment method.
     *
     * @param string $number            the new card number
     * @param bool   $store_unencrypted optional flag to store an uncrypted
     *                                  version of the card number as an
     *                                  internal property. This value is
     *                                  never saved in the database but can
     *                                  be retrieved for the lifetime of
     *                                  this object using the
     *                                  {@link StorePaymentMethod::getUnencryptedCardNumber()}
     *                                  method.
     *
     * @todo make this smart based on card type.
     *
     * @sensitive $number
     */
    public function setCardNumber($number, $store_unencrypted = false)
    {
        $this->card_number_preview = mb_substr($number, -4);

        if ($store_unencrypted) {
            $this->unencrypted_card_number = strval($number);
        }
    }

    /**
     * @return bool whether this objects contains an unencrypted card
     *              number
     */
    public function hasCardNumber()
    {
        return $this->unencrypted_card_number != '';
    }

    /**
     * Gets the unencrypted card number stored in this payment method.
     *
     * The card number must have been stored in this payment method using the
     * <i>$store_unencrypted</i> paramater on the
     * {@link StorePaymentMethod::setCardNumber()} method.
     *
     * @return string the unencrypted card number stored in this payment method
     */
    public function getUnencryptedCardNumber()
    {
        return $this->unencrypted_card_number;
    }

    /**
     * Displays this payment method.
     *
     * @param bool $display_details optional. Include additional details
     *                              for card-type payment methods.
     */
    public function display($display_details = true)
    {
        $span_tag = new SwatHtmlTag('span');
        $span_tag->class = 'store-payment-method';
        $span_tag->open();
        $this->displayInternal($display_details);
        $span_tag->close();
    }

    /**
     * Displays this payment method.
     *
     * This method is ideal for email.
     *
     * @param bool   $display_details optional. Include additional details
     *                                for card-type payment methods.
     * @param string $line_break      optional. The character or characters used to
     *                                represent line-breaks in the text display of
     *                                this payment method.
     */
    public function displayAsText($display_details = true, $line_break = "\n")
    {
        if ($this->payment_type->isCard()) {
            $this->displayCardAsText($display_details, $line_break);
        } elseif ($this->payment_type->isPayPal()) {
            echo $this->payment_type->title;
            $this->displayPayPalAsText($display_details, $line_break);
        }
    }

    public function copyFrom(StorePaymentMethod $method)
    {
        $fields = [
            'card_fullname',
            'card_number_preview',
            'card_expiry',
        ];

        foreach ($fields as $field_name) {
            $this->{$field_name} = $method->{$field_name};
        }

        $this->card_type = $method->getInternalValue('card_type');
        $this->payment_type = $method->getInternalValue('payment_type');
    }

    public function duplicate(): static
    {
        $new_payment_method = parent::duplicate();

        $fields = [
            'unencrypted_card_number',
        ];

        foreach ($fields as $field) {
            $new_payment_method->{$field} = $this->{$field};
        }

        return $new_payment_method;
    }

    /**
     * Whether or not this payment method should be saved with accounts.
     *
     * @return bool true if this payment method should be saved with
     *              accounts
     */
    public function isSaveableWithAccount()
    {
        $saveable = false;

        if ($this->payment_type !== null
            && $this->payment_type->isCard()) {
            $saveable = true;
        }

        return $saveable;
    }

    protected function init()
    {
        $this->id_field = 'integer:id';
        $this->registerInternalProperty(
            'payment_type',
            SwatDBClassMap::get('StorePaymentType')
        );

        $this->registerInternalProperty(
            'card_type',
            SwatDBClassMap::get('StoreCardType')
        );

        $this->registerDateProperty('card_expiry');
        $this->registerDateProperty('card_inception');
    }

    protected function getSerializablePrivateProperties()
    {
        $properties = parent::getSerializablePrivateProperties();
        $properties[] = 'unencrypted_card_number';

        return $properties;
    }

    protected function getKeyring()
    {
        $web_root = dirname($_SERVER['SCRIPT_FILENAME']);
        $system = dirname($web_root) . '/system';
        $keyrings = $system . '/keyrings';

        return $keyrings . '/site';
    }

    protected function displayInternal($display_details = true)
    {
        if ($this->payment_type->isCard()) {
            $this->card_type->display();

            if ($this->display_parts['card_number']) {
                $this->displayCard();
            }

            if ($display_details
                && (
                    $this->card_expiry instanceof SwatDate
                    && $this->display_parts['card_expiry']
                ) || (
                    $this->card_fullname != ''
                    && $this->display_parts['card_fullname']
                )
            ) {
                $this->displayCardDetails();
            }
        } elseif ($this->payment_type->isPayPal()) {
            echo SwatString::minimizeEntities($this->payment_type->title);
            $this->displayPayPal($display_details);
        } else {
            $this->payment_type->display();
        }
    }

    protected function displayCard()
    {
        $number_span = new SwatHtmlTag('span');
        $display_card = false;

        if ($this->payment_type->isCard()
            && $this->card_number_preview !== null) {
            $display_card = true;

            $number_span->setContent(StoreCardType::formatCardNumber(
                $this->card_number_preview,
                $this->card_type->getMaskedFormat()
            ));
        }

        if ($display_card) {
            $number_span->class = 'store-payment-method-card-number';
            echo ': ';
            $number_span->display();
        }
    }

    protected function displayCardDetails()
    {
        echo '<br />';
        $span_tag = new SwatHtmlTag('span');
        $span_tag->class = 'store-payment-method-info';
        $span_tag->open();

        if ($this->display_parts['card_expiry']
            && $this->card_expiry instanceof SwatDate) {
            printf(
                Store::_('Expiration Date: %s'),
                $this->card_expiry->formatLikeIntl(SwatDate::DF_CC_MY)
            );

            if ($this->display_parts['card_fullname']
                && $this->card_fullname != '') {
                echo ', ';
            }
        }

        if ($this->display_parts['card_fullname']
            && $this->card_fullname != '') {
            echo SwatString::minimizeEntities($this->card_fullname);
        }

        $span_tag->close();
    }

    protected function displayPayPal($display_details = true)
    {
        echo ': ';

        if ($display_details && $this->card_fullname !== null) {
            $span_tag = new SwatHtmlTag('span');
            $span_tag->setContent($this->card_fullname);
            $span_tag->class = 'store-payment-method-info';
            $span_tag->display();
        }

        $span_tag = new SwatHtmlTag('span');
        $span_tag->class = 'store-payment-method-paypal-email';
        $span_tag->open();

        if ($display_details) {
            echo ' &lt;';
        }

        echo SwatString::minimizeEntities($this->payer_email);

        if ($display_details) {
            echo '&gt;';
        }

        $span_tag->close();
    }

    protected function displayCardAsText($display_details, $line_break)
    {
        if ($this->display_parts['card_number']
            && $this->card_number_preview != '') {
            $this->card_type->display();
            echo ': ', StoreCardType::formatCardNumber(
                $this->card_number_preview,
                $this->card_type->getMaskedFormat()
            );
        }

        if ($display_details) {
            if ($this->display_parts['card_expiry']
                && $this->card_expiry instanceof SwatDate) {
                echo $line_break;
                printf(
                    Store::_('Expiration Date: %s'),
                    $this->card_expiry->formatLikeIntl(SwatDate::DF_CC_MY)
                );
            }

            if ($this->display_parts['card_fullname']
                && $this->card_fullname != '') {
                echo $line_break, $this->card_fullname;
            }
        }
    }

    protected function displayPayPalAsText($display_details, $line_break)
    {
        if ($this->card_number_preview !== null) {
            echo $line_break, $this->payer_email;
        }

        if ($display_details) {
            if ($this->card_fullname !== null) {
                echo $line_break, $this->card_fullname;
            }
        }
    }
}
