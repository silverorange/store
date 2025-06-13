<?php

/**
 * Payment provider for PayPal payments.
 *
 * Only the {@link StorePaymentProvider::pay()} and
 * {@link StorePaymentProvider::hold()} methods are implemented, corresponding
 * to the PayPal DirectPay "Sale" and "Authorization" actions.
 *
 * Additionally, methods to handle PayPal Express Checkout are provided.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
 */
class StorePayPalPaymentProvider extends StorePaymentProvider
{
    // {{{ class constants

    public const EXPRESS_CHECKOUT_URL_LIVE =
        'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=%s&useraction=%s';

    // @codingStandardsIgnoreStart
    public const EXPRESS_CHECKOUT_URL_SANDBOX =
        'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=%s&useraction=%s';
    // @codingStandardsIgnoreEnd
    // }}}
    // {{{ protected properties

    /**
     * The currency to use for transactions.
     *
     * @var string
     *
     * @see StorePayPalPaymentProvider::__construct()
     */
    protected $currency;

    /**
     * PayPal SOAP client.
     *
     * @var Payment_PayPal_SOAP
     *
     * @see StorePayPalPaymentProvider::__construct()
     */
    protected $client;

    /**
     * @var string
     *
     * @see StorePayPalPaymentProvider::__construct()
     */
    protected $mode;

    // }}}
    // {{{ public function __construct()

    /**
     * Creates a new payment provider using the PayPal SOAP API.
     *
     * Available parameters are:
     *
     * <kbd>mode</kbd>            - optional. Transaction mode to use. Must be
     *                              one of either 'live' or 'sandbox'. If not
     *                              specified, 'sandbox' is used.
     * <kbd>username</kbd>        - required. Username for PayPal
     *                              authentication.
     * <kbd>password</kbd>        - required. Password for PayPal
     *                              authentication.
     * <kbd>subject</kbd>         - optional. Third-party on behalf of whom
     *                              requests should be made. Use for
     *                              market-place type apps.
     * <kbd>signature</kbd>       - required. Signature used for signature-based
     *                              authentication.
     * <kbd>currency</kbd>        - required. The currency in which to perform
     *                              transactions.
     * <kbd>use_local_wsdl</kbd>  - optional. Whether or not to use a local
     *                              copy of the PayPal WSDL.
     *
     * @throws StoreException if a required parameter is missing or if the
     *                        'mode' paramater is not valid
     */
    public function __construct(array $parameters = [])
    {
        $required_parameters = [
            'username',
            'password',
            'signature',
            'currency',
        ];

        foreach ($required_parameters as $parameter) {
            if (!isset($parameters[$parameter])) {
                throw new StoreException('"' . $parameter . '" is required in the ' .
                    'PayPal payment provider parameters.');
            }
        }

        $this->currency = $parameters['currency'];

        $options = [
            'username'  => $parameters['username'],
            'password'  => $parameters['password'],
            'signature' => $parameters['signature'],
        ];

        if (!isset($parameters['mode'])) {
            $parameters['mode'] = 'sandbox';
        }

        if (isset($parameters['subject'])) {
            $options['subject'] = $parameters['subject'];
        }

        if (isset($parameters['use_local_wsdl'])) {
            $options['useLocalWsdl'] = $parameters['use_local_wsdl'];
        }

        $valid_modes = ['live', 'sandbox'];
        if (!in_array($parameters['mode'], $valid_modes)) {
            throw new StoreException('Mode "' . $parameters['mode'] . '" is not valid for ' .
                'the PayPal payment provider.');
        }

        $options['mode'] = $parameters['mode'];
        $this->mode = $parameters['mode'];

        $this->client = new Payment_PayPal_SOAP($options);
    }

    // }}}

    // direct payment methods
    // {{{ public function pay()

    /**
     * Pay for an order immediately.
     *
     * @param StoreOrder $order                   the order to pay for
     * @param string     $card_number             the card number to use for payment
     * @param string     $card_verification_value optional. Card verification value
     *                                            used for fraud prevention.
     *
     * @return StorePaymentMethodTransaction the transaction object for the
     *                                       payment. This object contains the
     *                                       transaction date and identifier.
     *
     * @sensitive $card_number
     * @sensitive $card_verification_value
     */
    public function pay(
        StoreOrder $order,
        $card_number,
        $card_verification_value = null
    ) {
        $request = $this->getDoDirectPaymentRequest(
            $order,
            'Sale',
            $card_number,
            $card_verification_value
        );

        try {
            $response = $this->client->call('DoDirectPayment', $request);
        } catch (Payment_PayPal_SOAP_ErrorException $e) {
            // ignore warnings
            foreach ($e as $error) {
                if ($error->getSeverity() !==
                    Payment_PayPal_SOAP::ERROR_WARNING) {
                    throw $e;
                }
            }
            $response = $e->getResponse();
        }

        if (!isset($response->TransactionID)) {
            $exception = new StorePaymentException(sprintf(
                'The following PayPal response does not contain a ' .
                "TransactionID:\n%s",
                print_r($response, true)
            ));

            $exception->processAndContinue();
        }

        $class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
        $transaction = new $class_name();

        $transaction->createdate = new SwatDate();
        $transaction->createdate->toUTC();
        $transaction->transaction_type = StorePaymentRequest::TYPE_PAY;
        $transaction->transaction_id = $response->TransactionID;

        return $transaction;
    }

    // }}}
    // {{{ public function hold()

    /**
     * Place a hold on funds for an order.
     *
     * @param StoreOrder $order                   the order to hold funds for
     * @param string     $card_number             the card number to place the hold on
     * @param string     $card_verification_value optional if AVS mode is set to
     *                                            off. The three-digit security
     *                                            code found on the reverse of
     *                                            cards or the four-digit security
     *                                            code found on the front of amex
     *                                            cards.
     *
     * @return StorePaymentMethodTransaction the transaction object for the
     *                                       payment. This object contains the
     *                                       transaction date and identifier.
     *
     * @sensitive $card_number
     * @sensitive $card_verification_value
     */
    public function hold(
        StoreOrder $order,
        $card_number,
        $card_verification_value = null
    ) {
        $request = $this->getDoDirectPaymentRequest(
            $order,
            'Authorization',
            $card_number,
            $card_verification_value
        );

        try {
            $response = $this->client->call('DoDirectPayment', $request);
        } catch (Payment_PayPal_SOAP_ErrorException $e) {
            // ignore warnings
            foreach ($e as $error) {
                if ($error->getSeverity() !==
                    Payment_PayPal_SOAP::ERROR_WARNING) {
                    throw $e;
                }
            }
            $response = $e->getResponse();
        }

        $class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
        $transaction = new $class_name();

        $transaction->createdate = new SwatDate();
        $transaction->createdate->toUTC();
        $transaction->transaction_type = StorePaymentRequest::TYPE_HOLD;
        $transaction->transaction_id = $response->TransactionID;

        return $transaction;
    }

    // }}}

    // express checkout payment methods
    // {{{ public function setExpressCheckout()

    /**
     * Initiates or updates an express checkout transaction.
     *
     * @param array $details         array of name-value pairs. Required parameters
     *                               are 'ReturnURL' and 'CancelURL'. If no
     *                               <kbd>$payment_details</kbd> are specified, the
     *                               'PaymentDetails' parameter is required.
     * @param array $payment_details array of name-value pairs. Required
     *                               parameters are 'OrderTotal'.
     *
     * @return string the token of the transaction
     *
     * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
     * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
     * @see StorePayPalPaymentProvider::doExpressCheckout()
     */
    public function setExpressCheckout(
        array $details,
        array $payment_details = []
    ) {
        $required_parameters = ['ReturnURL', 'CancelURL'];
        foreach ($required_parameters as $name) {
            if (!array_key_exists($name, $details)) {
                throw new StoreException('Required setExpressCheckout() ' .
                    '$details parameter "' . $name . '" is missing.');
            }
        }

        if ($payment_details == [] && isset($details['PaymentDetails'])) {
            $payment_details = $details['PaymentDetails'];
        }

        $required_parameters = ['OrderTotal'];
        foreach ($required_parameters as $name) {
            if (!array_key_exists($name, $payment_details)) {
                throw new StoreException('Required setExpressCheckout() ' .
                    '$payment_details parameter"' . $name . '" is missing.');
            }
        }

        $details['PaymentDetails'] = $payment_details;
        $request = $this->getSetExpressCheckoutRequest($details);

        try {
            $response = $this->client->call('SetExpressCheckout', $request);
        } catch (Payment_PayPal_SOAP_ErrorException $e) {
            // ignore warnings
            foreach ($e as $error) {
                if ($error->getSeverity() !==
                    Payment_PayPal_SOAP::ERROR_WARNING) {
                    throw $e;
                }
            }
            $response = $e->getResponse();
        }

        // According to the PayPal WSDL and SOAP schemas, it should be
        // impossible to get a response without a token, but due to incredible
        // incompetance on PayPal's end, the token is not returned if the token
        // is specified in the original array of parameters. In this case, just
        // return the token as it was passed in, as the response value should
        // be identical anyhow according to the documentation.
        if (isset($response->Token)) {
            $token = $response->Token;
        } else {
            if (isset($parameters['Token'])) {
                $token = $parameters['Token'];
            } else {
                throw new StoreException('No token returned in ' .
                    'SetExpressCheckout call.');
            }
        }

        return $token;
    }

    // }}}
    // {{{ public function getExpressCheckoutUri()

    /**
     * Gets the URI for PayPal's Express Checkout.
     *
     * Site code should relocate to this URI.
     *
     * @param string $token    the token of the current transaction
     * @param bool   $continue optional. Whether or not the customer should
     *                         will continue to a review page, or will commit
     *                         to payment without an additonal review step.
     *
     * @return string the URI to which the browser should be relocated to
     *                continue the Express Checkout transaction
     *
     * @see StorePayPalPaymentProvider::setExpressCheckout()
     * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
     * @see StorePayPalPaymentProvider::doExpressCheckout()
     */
    public function getExpressCheckoutUri($token, $continue = true)
    {
        $useraction = ($continue) ? 'continue' : 'commit';

        if ($this->mode === 'live') {
            $uri = sprintf(
                self::EXPRESS_CHECKOUT_URL_LIVE,
                urlencode($token),
                $useraction
            );
        } else {
            $uri = sprintf(
                self::EXPRESS_CHECKOUT_URL_SANDBOX,
                urlencode($token),
                $useraction
            );
        }

        return $uri;
    }

    // }}}
    // {{{ public function getExpressCheckoutDetails()

    /**
     * Updates an order with payment details from an Express Checkout
     * transaction.
     *
     * This sets the order payment method and order billing address.
     *
     * @param string             $token the token of the Express Checkout transaction for
     *                                  which to get the details
     * @param StoreOrder         $order the order object to update
     * @param MDB2_Driver_Common $db    the database. This is used for parsing
     *                                  addresses and payment types.
     *
     * @see StorePayPalPaymentProvider::setExpressCheckout()
     * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
     * @see StorePayPalPaymentProvider::doExpressCheckout()
     */
    public function getExpressCheckoutDetails(
        $token,
        StoreOrder $order,
        MDB2_Driver_Common $db
    ) {
        $request = $this->getGetExpressCheckoutDetailsRequest($token);

        try {
            $response = $this->client->call(
                'GetExpressCheckoutDetails',
                $request
            );
        } catch (Payment_PayPal_SOAP_ErrorException $e) {
            // ignore warnings
            foreach ($e as $error) {
                if ($error->getSeverity() !==
                    Payment_PayPal_SOAP::ERROR_WARNING) {
                    throw $e;
                }
            }
            $response = $e->getResponse();
        }

        $details = $response->GetExpressCheckoutDetailsResponseDetails;

        $payment_method = $order->payment_methods->getByPayPalToken($token);
        if ($payment_method === null) {
            $payment_method = $this->getStoreOrderPaymentMethod($db);
            $order->payment_methods->add($payment_method);
        }
        $this->updateStoreOrderPaymentMethod(
            $payment_method,
            $token,
            $details->PayerInfo,
            $db
        );

        // set billing address if it was returned
        if (isset($details->BillingAddress, $details->BillingAddress->Country)
        ) {
            $billing_address = $this->getStoreOrderAddress(
                $details->BillingAddress,
                $db
            );

            // Only set address if it is not already set or if it is not the
            // same as the existing billing address.
            if ($order->billing_address === null
                || !$order->billing_address->compare($billing_address)) {
                $order->billing_address = $billing_address;
            }
        }

        // set shipping address if it was returned
        if (isset($details->PayerInfo->Address->Country)) {
            $shipping_address = $this->getStoreOrderAddress(
                $details->PayerInfo->Address,
                $db
            );

            // Only set address if it is not already set or if it is not the
            // same as the existing shipping address.
            if ($order->shipping_address === null
                || !$order->shipping_address->compare($shipping_address)) {
                $order->shipping_address = $shipping_address;
            }

            // Only set billing address if it is not already set.
            if ($order->billing_address === null) {
                $order->billing_address = $order->shipping_address;
            }
        }

        if ($order->email === null) {
            $order->email = $details->PayerInfo->Payer;
        }

        if (isset($details->ContactPhone) && $order->phone === null) {
            $order->phone = $details->ContactPhone;
        }
    }

    // }}}
    // {{{ public function doExpressCheckout()

    /**
     * Completes an Express Checkout payment.
     *
     * The <kbd>$action</kbd> must be the same value as the original request
     * that generated the <kbd>$token</kbd>.
     *
     * @param string     $token      the token of the active transaction
     * @param string     $action     one of 'Sale' or 'Authorization' or 'Order'
     * @param string     $payer_id   payPal payer identification number as returned
     *                               by the <kbd>getExpressCheckout()</kbd> method
     * @param StoreOrder $order      the order to pay for
     * @param string     $notify_url optional. The URL where the Instant Payment
     *                               Notification (IPN) from PayPal should be
     *                               sent. If not specified, the IPN will be sent
     *                               to the URL set in your PayPal account.
     * @param string     $custom     optional. A custom value that is passed through
     *                               with your order. This value will be present in
     *                               the IPN if it is specified.
     *
     * @return array a two element array containing a
     *               {@link StorePaymentMethodTransaction} object representing
     *               the transaction as well as an object containing the
     *               detailed response from PayPal. The array keys are:
     *               <kbd>transaction</kbd> and <kbd>details</kbd>
     *               respectively.
     *
     * @see StorePayPalPaymentProvider::setExpressCheckout()
     * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
     * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
     */
    public function doExpressCheckout(
        $token,
        $action,
        $payer_id,
        StoreOrder $order,
        $notify_url = '',
        $custom = ''
    ) {
        switch ($action) {
            case 'Authorizarion':
                $transaction_type = StorePaymentRequest::TYPE_HOLD;
                break;

            case 'Sale':
            case 'Order':
            default:
                $transaction_type = StorePaymentRequest::TYPE_PAY;
                break;
        }

        $request = $this->getDoExpressCheckoutPaymentRequest(
            $token,
            $action,
            $payer_id,
            $order
        );

        try {
            $response = $this->client->call(
                'DoExpressCheckoutPayment',
                $request
            );
        } catch (Payment_PayPal_SOAP_ErrorException $e) {
            // ignore warnings
            foreach ($e as $error) {
                if ($error->getSeverity() !==
                    Payment_PayPal_SOAP::ERROR_WARNING) {
                    throw $e;
                }
            }
            $response = $e->getResponse();
        }

        $details = $response->DoExpressCheckoutPaymentResponseDetails;

        $class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
        $transaction = new $class_name();

        $transaction->createdate = new SwatDate();
        $transaction->createdate->toUTC();
        $transaction->transaction_type = $transaction_type;
        $transaction->transaction_id = $details->PaymentInfo->TransactionID;

        return [
            'transaction' => $transaction,
            'details'     => $details,
        ];
    }

    // }}}
    // {{{ public function createRecurringPaymentsProfile()

    /**
     * @sensitive $card_number
     * @sensitive $card_verification_value
     *
     * @param mixed      $profile_id
     * @param mixed|null $card_number
     * @param mixed|null $card_verification_value
     */
    public function createRecurringPaymentsProfile(
        StoreOrderPaymentMethod $payment_method,
        StoreOrder $order,
        $profile_id,
        SwatDate $start_date,
        array $schedule_details,
        $card_number = null,
        $card_verification_value = null
    ) {
        $request = $this->getCreateRecurringPaymentsProfileRequest(
            $payment_method,
            $order,
            $profile_id,
            $start_date,
            $schedule_details,
            $card_number,
            $card_verification_value
        );

        try {
            $response = $this->client->call(
                'CreateRecurringPaymentsProfile',
                $request
            );
        } catch (Payment_PayPal_SOAP_ErrorException $e) {
            // ignore warnings
            foreach ($e as $error) {
                if ($error->getSeverity() !==
                    Payment_PayPal_SOAP::ERROR_WARNING) {
                    throw $e;
                }
            }
            $response = $e->getResponse();
        }

        $details = $response->CreateRecurringPaymentsProfileResponseDetails;

        return [
            'profile_id'     => $details->ProfileID,
            'profile_status' => $details->ProfileStatus,
        ];
    }

    // }}}

    // convenience methods
    // {{{ public function getExceptionMessageId()

    /**
     * Gets an error message id from a Payment_PayPal_SOAP_ErrorException.
     *
     * @param Throwable $e the exception
     *
     * @return string the error message id
     *
     * @see StoreCheckoutConfirmationPage::getErrorMessage()
     */
    public function getExceptionMessageId(Throwable $e)
    {
        if (!$e instanceof Payment_PayPal_SOAP_ErrorException) {
            return null;
        }

        $code = 0;

        foreach ($e as $error) {
            if ($error->getSeverity() === Payment_PayPal_SOAP::ERROR_ERROR) {
                // get first error code
                $code = $error->getCode();
                break;
            }
            if ($code === 0) {
                // otherwise get first non-error code
                $code = $error->getCode();
            }
        }

        switch ($code) {
            /*
             * Generic error. This can mean anything from PayPal is broken to the
             * card number is not valid.
             */
            case 10001:
                return 'card-error';

                // Invalid card number (4111 1111 1111 1111 for example)
            case 10759:
                return 'card-not-valid';

                // Card rejected by PayPal
            case 15001:
            case 15002:
                return 'card-not-valid';

                // Card declined by issuing bank
            case 15005:
                return 'card-not-valid';

                // Card type mismatch from issuing bank
            case 15006:
                return 'card-type';

                // Card expired from issuing bank
            case 15007:
                return 'card-expired';

                /*
                 * CVV2 error. May be one of:
                 *
                 *  - CVV2 does not match
                 *  - CVV2 was not processed by PayPal
                 *  - CVV2 was not provided by merchant (us)
                 *  - CVV2 is not supported by card issuer
                 *  - No response from card issuer
                 */
            case 10725:
            case 15004:
                $cvv2_code = $e->getResponse()->CVV2Code;
                switch ($cvv2_code) {
                    // CVV2 does not match
                    case 'N':
                        return 'card-verification-value';

                        // Everything else gets a generic error message.
                    case 'P':
                    case 'S':
                    case 'U':
                    case 'X':
                    default:
                        return 'card-error';
                }

                break;

                /*
                 * AVS error. There are a number of possible errors as documented
                 * within.
                 */
            case 10505:
            case 10555:
                $avs_code = $e->getResponse()->AVSCode;
                switch ($avs_code) {
                    // Postal code does not match.
                    case 'A':
                    case 'B':
                        return 'postal-code-mismatch';

                        // Either the whole address or the street address do not match.
                    case 'C':
                    case 'N':
                    case 'P':
                    case 'W':
                    case 'Z':
                        return 'address-mismatch';

                        // Service unavailable or other errors. Generic error message.
                    case 'E':
                    case 'G':
                    case 'I':
                    case 'R':
                    case 'S':
                    case 'U':
                    default:
                        return 'card-error';
                }

                break;

                /*
                 * Express checkout shipping address did not pass PalPal's
                 * address verification check.
                 *
                 * PayPal checks the City/State/ZIP and fails if they don't match.
                 */
            case 10736:
                return 'paypal-address-error';

                // PayPal does not allow purchases from some countries
            case 10745:
            case 15011:
                return 'paypal-country-error';

                // PayPal shipping country must be the same as billing country.
            case 10474:
                return 'paypal-shipping-country-error';

                /*
                 * Gateway declined. This happens when the issuing bank declines the
                 * transaction.
                 */
            case 10752:
                return 'card-error';

                /*
                 * Invalid funding source selected in express checkout. Customer must
                 * return to PayPal and select another funding source.
                 */
            case 10422:
                return 'paypal-payment-error';

                /*
                 * Transaction cannot be processed at this time. Happens when Visa
                 * or MasterCard servers go down.
                 */
            case 10445:
            case 10764:
                return 'card-error';

                /*
                 * Some kind of generic AVS rate limiting error. Who knows what the
                 * fuck this really means because it's not documented besides
                 * "Gateway Error". It happens occasionally though.
                 */
            case 10564:
                return 'card-error';

                /*
                 * Transaction was declined by PayPal. Customer must contact PayPal
                 * for moreinformation
                 */
            case 10754:
            case 10544:
                return 'card-error';

                /*
                 * PayPal Express Checkout transaction can not complete. Instruct the
                 * customer to use an alternative payment method.
                 */
            case 10417:
                return 'payment-error';

                /*
                 * ExpressCheckout session has expired token is invalid. Checkout
                 * needs to be restarted.
                 */
            case 10411:
            case 11502:
                return 'paypal-expired-token';
        }

        return null;
    }

    // }}}
    // {{{ public function formatNumber()

    /**
     * @param float $value
     *
     * @return string formatted order total
     */
    public function formatNumber($value)
    {
        $value = SwatNumber::roundToEven($value, 2);

        return number_format($value, 2, '.', '');
    }

    // }}}
    // {{{ public function formatString()

    /**
     * @param string $string
     * @param mixed  $max_length
     *
     * @return string
     */
    public function formatString($string, $max_length = 0)
    {
        // convert to iso-8859-1
        $string = iconv('utf-8', 'ASCII//TRANSLIT', $string);

        // truncate to max_length
        if ($max_length > 0) {
            $string = mb_substr($string, 0, $max_length);
        }

        return $string;
    }

    // }}}
    // {{{ public function formatCurrency()

    public function formatCurrency($value)
    {
        return $this->getCurrencyValue($value, $this->currency);
    }

    // }}}
    // {{{ public function getCurrencyValue()

    /**
     * @param float $value
     * @param mixed $currency
     *
     * @return string formatted order total
     */
    public function getCurrencyValue($value, $currency)
    {
        return [
            '_'          => $this->formatNumber($value),
            'currencyID' => $currency,
        ];
    }

    // }}}
    // {{{ public function getPaymentDetails()

    public function getPaymentDetails(
        StoreOrder $order,
        $notify_url = '',
        $custom = ''
    ) {
        $details = [];

        $details['OrderTotal'] =
            $this->getCurrencyValue($order->total, $this->currency);

        $description = $order->getDescription();
        $description = $this->formatString($description, 127);
        if ($description != '') {
            $details['OrderDescription'] = $description;
        }

        $details['ItemTotal'] =
            $this->getCurrencyValue($order->item_total, $this->currency);

        $details['ShippingTotal'] =
            $this->getCurrencyValue($order->shipping_total, $this->currency);

        $details['HandlingTotal'] =
            $this->getCurrencyValue($order->surcharge_total, $this->currency);

        $details['TaxTotal'] =
            $this->getCurrencyValue($order->tax_total, $this->currency);

        if ($order->id !== null) {
            $details['InvoiceID'] = $order->id;
        }

        if ($order->shipping_address instanceof StoreOrderAddress
            && $order->shipping_address->getInternalValue('country') !== null) {
            $details['ShipToAddress'] = $this->getShipToAddress($order);
        }

        if ($notify_url != '') {
            $details['NotifyURL'] = $this->formatString($notify_url, 2048);
        }

        if ($custom != '') {
            $details['Custom'] = $this->formatString($custom, 256);
        }

        $items = $this->getPaymentDetailsItems($order);
        if (count($items) > 0) {
            $details['PaymentDetailsItem'] = $items;
        }

        return $details;
    }

    // }}}
    // {{{ public function getPaymentDetailsItems()

    public function getPaymentDetailsItems(StoreOrder $order)
    {
        $details = [];

        foreach ($order->items as $item) {
            $details[] = $this->getPaymentDetailsItem($item);
        }

        return $details;
    }

    // }}}
    // {{{ public function getPaymentDetailsItem()

    public function getPaymentDetailsItem(StoreOrderItem $item)
    {
        $details = [];

        $name = $item->product_title;
        $description = $item->getDescription();

        // strip HTML formatting
        $description = preg_replace('/<.*?\>/', ' ', $description);

        // collapse whitespace
        $description = preg_replace('/\s+/', ' ', $description);

        // add to item name
        if ($description != '') {
            $name .= ' - ' . $description;
        }

        $details['Name'] = $this->formatString($name, 127);

        if ($item->sku != '') {
            $details['Number'] = $item->sku;
        }

        $details['Amount'] = $this->getCurrencyValue(
            $item->price,
            $this->currency
        );

        $details['Quantity'] = $item->quantity;

        return $details;
    }

    // }}}

    // data-structure helper methods (express checkout)
    // {{{ protected function getSetExpressCheckoutRequest()

    protected function getSetExpressCheckoutRequest(array $parameters)
    {
        if (isset($parameters['PaymentDetails']['OrderTotal'])
            && !is_array($parameters['PaymentDetails']['OrderTotal'])) {
            $parameters['PaymentDetails']['OrderTotal'] =
                $this->getCurrencyValue(
                    $parameters['PaymentDetails']['OrderTotal'],
                    $this->currency
                );
        }

        if (isset($parameters['PaymentDetails']['ShipToAddress'])
            && $parameters['PaymentDetails']['ShipToAddress'] instanceof StoreOrderAddress) {
            $parameters['PaymentDetails']['ShipToAddress'] =
                $this->getAddress(
                    $parameters['PaymentDetails']['ShipToAddress']
                );
        }

        return [
            'SetExpressCheckoutRequest' => [
                'Version'                          => '62.0',
                'SetExpressCheckoutRequestDetails' => $parameters,
            ],
        ];
    }

    // }}}
    // {{{ protected function getGetExpressCheckoutDetailsRequest()

    protected function getGetExpressCheckoutDetailsRequest($token)
    {
        return [
            'GetExpressCheckoutDetailsRequest' => [
                'Version' => '62.0',
                'Token'   => $token,
            ],
        ];
    }

    // }}}
    // {{{ protected function getDoExpressCheckoutPaymentRequest()

    protected function getDoExpressCheckoutPaymentRequest(
        $token,
        $action,
        $payer_id,
        StoreOrder $order,
        $notify_url = '',
        $custom = ''
    ) {
        return [
            'DoExpressCheckoutPaymentRequest' => [
                'Version'                                => '62.0',
                'DoExpressCheckoutPaymentRequestDetails' => $this->getDoExpressCheckoutPaymentRequestDetails(
                    $token,
                    $action,
                    $payer_id,
                    $order,
                    $notify_url,
                    $custom
                ),
            ],
        ];
    }

    // }}}
    // {{{ protected function getDoExpressCheckoutPaymentRequestDetails()

    protected function getDoExpressCheckoutPaymentRequestDetails(
        $token,
        $action,
        $payer_id,
        StoreOrder $order,
        $notify_url = '',
        $custom = ''
    ) {
        $details = [];

        $details['Token'] = $token;
        $details['PaymentAction'] = $action;
        $details['PayerID'] = $payer_id;
        $details['PaymentDetails'] = $this->getPaymentDetails(
            $order,
            $notify_url,
            $custom
        );

        return $details;
    }

    // }}}
    // {{{ protected function getStoreOrderPaymentMethod()

    protected function getStoreOrderPaymentMethod(MDB2_Driver_Common $db)
    {
        $class_name = SwatDBClassMap::get('StoreOrderPaymentMethod');
        $payment_method = new $class_name();

        $class_name = SwatDBClassMap::get('StorePaymentType');
        $payment_type = new $class_name();
        $payment_type->setDatabase($db);

        if ($payment_type->loadByShortname('paypal')) {
            $payment_method->payment_type = $payment_type;
        }

        return $payment_method;
    }

    // }}}
    // {{{ protected function getStoreOrderAddress()

    protected function getStoreOrderAddress($address, MDB2_Driver_Common $db)
    {
        $class_name = SwatDBClassMap::get('StoreOrderAddress');
        $order_address = new $class_name();

        $order_address->fullname = $address->Name;
        $order_address->line1 = $address->Street1;
        $order_address->city = $address->CityName;
        $order_address->postal_code = $address->PostalCode;
        $order_address->country = $address->Country;

        if ($address->Street2 != '') {
            $order_address->line2 = $address->Street2;
        }

        // PayPal sometimes returns an abbreviation and sometimes returns the
        // full title. Go figure.
        if ($address->StateOrProvince != '') {
            $class_name = SwatDBClassMap::get('StoreProvState');
            $provstate = new $class_name();
            $provstate->setDatabase($db);
            if (mb_strlen($address->StateOrProvince) === 2) {
                if ($provstate->loadFromAbbreviation(
                    $address->StateOrProvince,
                    $address->Country
                )) {
                    $order_address->provstate = $provstate;
                } else {
                    $order_address->provstate_other = $address->StateOrProvince;
                }
            } else {
                if ($provstate->loadFromTitle(
                    $address->StateOrProvince,
                    $address->Country
                )) {
                    $order_address->provstate = $provstate;
                } else {
                    $order_address->provstate_other = $address->StateOrProvince;
                }
            }
        }

        return $order_address;
    }

    // }}}
    // {{{ protected function getStoreFullname()

    protected function getStoreFullname($person_name)
    {
        $name = [];

        if ($person_name->Salutation != '') {
            $name[] = $person_name->Salutation;
        }
        if ($person_name->FirstName != '') {
            $name[] = $person_name->FirstName;
        }
        if ($person_name->MiddleName != '') {
            $name[] = $person_name->MiddleName;
        }
        if ($person_name->LastName != '') {
            $name[] = $person_name->LastName;
        }
        if ($person_name->Suffix != '') {
            $name[] = $person_name->Suffix;
        }

        return implode(' ', $name);
    }

    // }}}
    // {{{ protected function updateStoreOrderPaymentMethod()

    protected function updateStoreOrderPaymentMethod(
        StoreOrderPaymentMethod $payment_method,
        $token,
        $payer_info
    ) {
        $payment_method->setPayPalToken($token);

        $fullname = $this->getStoreFullname($payer_info->PayerName);

        $payment_method->card_fullname = $fullname;
        $payment_method->payer_email = $payer_info->Payer;
        $payment_method->payer_id = $payer_info->PayerID;

        return $payment_method;
    }

    // }}}

    // data-structure helper methods (direct)
    // {{{ protected function getDoDirectPaymentRequest()

    protected function getDoDirectPaymentRequest(
        StoreOrder $order,
        $action,
        $card_number,
        $card_verification_value
    ) {
        return [
            'DoDirectPaymentRequest' => [
                'Version'                       => '1.0',
                'DoDirectPaymentRequestDetails' => $this->getDoDirectPaymentRequestDetails(
                    $order,
                    $action,
                    $card_number,
                    $card_verification_value
                ),
            ],
        ];
    }

    // }}}
    // {{{ protected function getDoDirectPaymentRequestDetails()

    protected function getDoDirectPaymentRequestDetails(
        StoreOrder $order,
        $action,
        $card_number,
        $card_verification_value
    ) {
        $payment_method = $order->payment_methods->getFirst();

        $details = [];

        $details['PaymentAction'] = $action;
        $details['PaymentDetails'] = $this->getPaymentDetails($order);
        $details['CreditCard'] = $this->getCreditCardDetails(
            $order,
            $payment_method,
            $card_number,
            $card_verification_value
        );

        $details['IPAddress'] = $this->getIpAddress();
        $details['MerchantSessionID'] = $this->getMerchantSessionId();

        return $details;
    }

    // }}}
    // {{{ protected function getPayerInfo()

    protected function getPayerInfo(
        StoreOrder $order,
        StorePaymentMethod $payment_method
    ) {
        $details = [];

        if ($order->email != '') {
            $details['Payer'] = $order->email;
        }

        $details['PayerName'] = $this->getPersonName($payment_method);

        if ($order->billing_address instanceof StoreAddress) {
            $details['PayerCountry'] =
                $order->billing_address->getInternalValue('country');

            $details['Address'] = $this->getPayerInfoAddress($order);
        }

        return $details;
    }

    // }}}
    // {{{ protected function getPayerInfoAddress()

    protected function getPayerInfoAddress(StoreOrder $order)
    {
        return $this->getAddress($order->billing_address);
    }

    // }}}
    // {{{ protected function getPersonName()

    protected function getPersonName(StoreOrderPaymentMethod $payment_method)
    {
        $fullname = $payment_method->card_fullname;

        $midpoint = intval(floor(mb_strlen($fullname) / 2));

        // get space closest to the middle of the string
        $left_pos = mb_strrpos(
            $fullname,
            ' ',
            -mb_strlen($fullname) + $midpoint
        );

        $right_pos = mb_strpos($fullname, ' ', $midpoint);

        if ($left_pos === false && $right_pos === false) {
            // There is no first and last name division, just split string for
            // PayPal's sake.
            $pos = $midpoint;
        } elseif ($left_pos === false) {
            $pos = $right_pos;
        } elseif ($right_pos === false) {
            $pos = $left_pos;
        } elseif (($midpoint - $left_pos) <= ($right_pos - $midpoint)) {
            $pos = $left_pos;
        } else {
            $pos = $right_pos;
        }

        // split name into first and last parts in roughly the middle
        if ($pos === false) {
            $first_name = mb_substr($fullname, 0, $midpoint);
            $last_name = mb_substr($fullname, $midpoint);
        } else {
            $first_name = mb_substr($fullname, 0, $pos);
            $last_name = mb_substr($fullname, $pos + 1);
        }

        $details = [];

        $details['FirstName'] = $this->formatString($first_name, 25);
        $details['LastName'] = $this->formatString($last_name, 25);

        return $details;
    }

    // }}}

    // data-structure helper methods (recurring payments)
    // {{{ protected function getCreateRecurringPaymentsProfileRequest()

    /**
     * @sensitive $card_number
     * @sensitive $card_verification_value
     *
     * @param mixed      $profile_id
     * @param mixed|null $card_number
     * @param mixed|null $card_verification_value
     */
    protected function getCreateRecurringPaymentsProfileRequest(
        StoreOrderPaymentMethod $payment_method,
        StoreOrder $order,
        $profile_id,
        SwatDate $start_date,
        array $schedule_details,
        $card_number = null,
        $card_verification_value = null
    ) {
        return [
            'CreateRecurringPaymentsProfileRequest' => [
                'Version'                                      => '60.0',
                'CreateRecurringPaymentsProfileRequestDetails' => $this->getCreateRecurringPaymentsProfileRequestDetails(
                    $payment_method,
                    $order,
                    $profile_id,
                    $start_date,
                    $schedule_details,
                    $card_number,
                    $card_verification_value
                ),
            ],
        ];
    }

    // }}}
    // {{{ protected function getCreateRecurringPaymentsProfileRequestDetails()

    /**
     * @sensitive $card_number
     * @sensitive $card_verification_value
     *
     * @param mixed      $profile_id
     * @param mixed|null $card_number
     * @param mixed|null $card_verification_value
     */
    protected function getCreateRecurringPaymentsProfileRequestDetails(
        StoreOrderPaymentMethod $payment_method,
        StoreOrder $order,
        $profile_id,
        SwatDate $start_date,
        array $schedule_details,
        $card_number = null,
        $card_verification_value = null
    ) {
        $details = [];

        switch ($payment_method->payment_type->shortname) {
            case 'paypal':
                $details['Token'] = $this->formatString(
                    $payment_method->getPayPalToken(),
                    127
                );

                break;

            case 'card':
            default:
                $details['CreditCard'] = $this->getCreditCardDetails(
                    $order,
                    $payment_method,
                    $card_number,
                    $card_verification_value
                );

                break;
        }

        $items = $this->getRecurringPaymentsPaymentDetailsItems($order);
        if (count($items) > 0) {
            $details['PaymentDetailsItem'] = $items;
        }

        $details['RecurringPaymentsProfileDetails'] =
            $this->getRecurringPaymentsProfileDetails(
                $order,
                $profile_id,
                $start_date
            );

        $details['ScheduleDetails'] = $schedule_details;

        return $details;
    }

    // }}}
    // {{{ protected function getRecurringPaymentsPaymentDetailsItems()

    protected function getRecurringPaymentsPaymentDetailsItems(
        StoreOrder $order
    ) {
        $details = [];

        foreach ($order->items as $item) {
            $details[] = $this->getPaymentDetailsItem($item);
        }

        return $details;
    }

    // }}}
    // {{{ protected function getRecurringPaymentsProfileDetails()

    protected function getRecurringPaymentsProfileDetails(
        StoreOrder $order,
        $profile_id,
        SwatDate $start_date
    ) {
        $details = [];

        $details['SubscriberName'] = $this->formatString(
            $order->billing_address->getFullname(),
            32
        );

        if ($order->shipping_address instanceof StoreAddress) {
            $details['SubscriberShippingAddress'] =
                $this->getShipToAddress($order);
        }

        $details['BillingStartDate'] = $start_date->getISO8601();
        $details['ProfileReference'] = $this->formatString($profile_id, 127);

        return $details;
    }

    // }}}

    // data-structure helper methods (shared)
    // {{{ protected function getCreditCardDetails()

    protected function getCreditCardDetails(
        StoreOrder $order,
        StoreOrderPaymentMethod $payment_method,
        $card_number,
        $card_verification_value
    ) {
        $details = [];

        $details['CardOwner'] = $this->getPayerInfo($order, $payment_method);

        $details['CreditCardType'] = $this->getCreditCardType($payment_method);

        $details['CreditCardNumber'] = $card_number;

        $expiry = $payment_method->card_expiry;
        $details['ExpMonth'] = $expiry->formatLikeIntl('MM');
        $details['ExpYear'] = $expiry->formatLikeIntl('yyyy');

        if ($card_verification_value != '') {
            $details['CVV2'] = $card_verification_value;
        }

        if ($payment_method->card_inception !== null) {
            $inception = $payment_method->card_inception;
            $details['StartMonth'] = $inception->formatLikeIntl('MM');
            $details['StartYear'] = $inception->formatLikeIntl('yyyy');
        }

        if ($payment_method->card_issue_number != '') {
            $details['IssueNumber'] = $payment_method->card_issue_number;
        }

        return $details;
    }

    // }}}
    // {{{ protected function getCreditCardType()

    protected function getCreditCardType(StoreOrderPaymentMethod $payment_method)
    {
        switch ($payment_method->card_type->shortname) {
            case 'amex':
                $type = 'Amex';
                break;

            case 'discover':
                $type = 'Discover';
                break;

            case 'mastercard':
                $type = 'MasterCard';
                break;

            case 'visa':
                $type = 'Visa';
                break;

            case 'switch':
                $type = 'Switch';
                break;

            case 'solo':
                $type = 'Solo';
                break;

            default:
                throw new StorePaymentCardTypeException('Unsupported card type in order.');
        }

        return $type;
    }

    // }}}
    // {{{ protected function getShipToAddress()

    protected function getShipToAddress(StoreOrder $order)
    {
        return $this->getAddress($order->shipping_address);
    }

    // }}}
    // {{{ protected function getAddress()

    protected function getAddress(StoreOrderAddress $address)
    {
        $details = [];

        $details['Name'] = $this->formatString($address->getFullname(), 32);
        $details['Street1'] = $this->formatString($address->line1, 100);

        if ($address->line2 != '') {
            $details['Street2'] = $this->formatString($address->line2, 100);
        }

        $details['CityName'] = $this->formatString($address->city, 40);

        if ($address->getInternalValue('provstate') !== null) {
            $details['StateOrProvince'] = $address->provstate->abbreviation;
        } else {
            $details['StateOrProvince'] = $this->formatString(
                $address->provstate_other,
                40
            );
        }

        $details['PostalCode'] = $this->formatString($address->postal_code, 20);
        $details['Country'] = $address->getInternalValue('country');

        if ($address->phone != '') {
            $details['Phone'] = $this->formatString($address->phone, 20);
        }

        return $details;
    }

    // }}}

    // general helper methods
    // {{{ protected function getMerchantSessionId()

    protected function getMerchantSessionId()
    {
        // Note: PayPal's documentation states this should only contain
        // numeric characters, however the conversion to base-10 from base-64
        // is not easy in PHP. Numerous examples online use alphanumeric
        // characters in this field.
        return session_id();
    }

    // }}}
    // {{{ protected function getIpAddress()

    protected function getIpAddress()
    {
        $remote_ip = null;

        if (isset($_SERVER['HTTP_X_FORWARDED_IP'])) {
            $remote_ip = $_SERVER['HTTP_X_FORWARDED_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $remote_ip = $_SERVER['REMOTE_ADDR'];
        }

        return $remote_ip;
    }

    // }}}
}
