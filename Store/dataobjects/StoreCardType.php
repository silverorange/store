<?php

/**
 * A card type data object.
 *
 * Card type shortnames are by convention:
 *
 * <pre>
 * Shortname    | Description         | Type              | Region
 * -------------+---------------------+-------------------+--------
 * visa         | Visa                | credit card       | Global
 * mastercard   | MasterCard          | credit card       | Global
 * maestro      | Maestro             | debit card        | Global
 * delta        | Visa Debit/Delta    | debit card        | UK
 * solo         | Solo                | debit card        | UK
 * switch       | UK Maestro (Switch) | debit card        | UK
 * electron     | Visa Electron       | credit/debit card | Outside US+CA+AU
 * amex         | American Express    | credit card       | Global
 * dinersclub   | Diners Club         | credit card       | US+CA
 * jcb          | Japan Credit Bureau | credit card       | JA
 * discover     | Discover Card       | credit card       | US
 * unionpay     | China UnionPay      | credit card       | CH
 * </pre>
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int     $id
 * @property ?string $shortname
 * @property ?string $title
 * @property ?string $note
 * @property ?int    $displayorder
 */
class StoreCardType extends SwatDBDataObject
{
    /**
     * Unique identifier of this card type.
     *
     * @var string
     */
    public $id;

    /**
     * Non-visible string indentifier.
     *
     * This is something like 'VS', 'MC' or 'DS'.
     *
     * @var string
     */
    public $shortname;

    /**
     * User visible title for this card type.
     *
     * @var string
     */
    public $title;

    /**
     * User visible note for this card type.
     *
     * The note field should be used to inform customers of additional
     * requirements or conditions on this card type.
     *
     * @var string
     */
    public $note;

    /**
     * Order of display.
     *
     * @var int
     */
    public $displayorder;

    /**
     * Loads a card type by its shortname.
     *
     * @param string $shortname the shortname of the card type to load
     */
    public function loadFromShortname($shortname)
    {
        $this->checkDB();
        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where shortname = %s',
                $this->table,
                $this->db->quote($shortname, 'text')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    /**
     * Whether or not this card type is available in the given region.
     *
     * The type needs to have an id before this method will work.
     *
     * @param StoreRegion $region the region to check the availability of this
     *                            card type in
     *
     * @return bool true if this card type is available in the
     *              given region and false if it is not
     *
     * @throws StoreException if this card type has no id defined
     */
    public function isAvailableInRegion(StoreRegion $region)
    {
        $this->checkDB();

        if ($this->id === null) {
            throw new StoreException('Card type must have an id set ' .
                'before region availability can be determined.');
        }

        $sql = sprintf(
            'select count(id) from CardType
			inner join CardTypeRegionBinding on card_type = id and
				region = %s
			where id = %s',
            $this->db->quote($region->id, 'integer'),
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::queryOne($this->db, $sql) > 0;
    }

    /**
     * Gets the masked format string for this card type.
     *
     * @return string the masked format string for this card type. If no
     *                suitable mask is available then null is returned.
     *
     * @see StoreCardType::formatCardNumber()
     */
    public function getMaskedFormat()
    {
        $mask = null;

        switch ($this->shortname) {
            case 'visa':
            case 'mastercard':
            case 'discover':
            case 'jcb':
            case 'electron':
            case 'unionpay':
            case 'delta':
            case 'switch':
            case 'solo':
                $mask = '**** **** **** ####';
                break;

            case 'amex':
                $mask = '**** ****** #####';
                break;

            case 'dinersclub':
                $mask = '**** ****** ####';
                break;
        }

        return $mask;
    }

    /**
     * Gets the format string for this card type.
     *
     * @return string the format string for this cart type. If no suitable
     *                suitable format is available then null is returned.
     *
     * @see StoreCardType::formatCardNumber()
     */
    public function getFormat()
    {
        $mask = null;

        switch ($this->shortname) {
            case 'visa':
            case 'mastercard':
            case 'discover':
            case 'jcb':
            case 'electron':
            case 'unionpay':
            case 'delta':
            case 'switch':
            case 'solo':
                $mask = '#### #### #### ####';
                break;

            case 'amex':
                $mask = '#### ###### #####';
                break;

            case 'dinersclub':
                $mask = '#### ###### ####';
                break;
        }

        return $mask;
    }

    /**
     * Gets the length of the card number preview field for this card type.
     *
     * @return int the length of the card number preview field for this
     *             card type. If this card type is not a known debit
     *             or credit card, the card number preview length is
     *             returned as zero.
     */
    public function getNumberPreviewLength()
    {
        $length = 0;

        switch ($this->shortname) {
            case 'visa':
            case 'mastercard':
            case 'discover':
            case 'jcb':
            case 'electron':
            case 'unionpay':
            case 'delta':
            case 'switch':
            case 'solo':
            case 'dinersclub':
                $length = 16;
                break;

            case 'amex':
                $length = 15;
                break;
        }

        return $length;
    }

    /**
     * Gets the length of the card verification value for this card type.
     *
     * @return int the length of the card verification value for this
     *             card type. If this card type is not a known debit
     *             or credit card, the card verification value length is
     *             returned as zero.
     */
    public function getCardVerificationValueLength()
    {
        $length = null;

        switch ($this->shortname) {
            case 'visa':
            case 'mastercard':
            case 'jcb':
            case 'electron':
            case 'unionpay':
            case 'delta':
            case 'switch':
            case 'solo':
            case 'dinersclub':
            case 'discover':
                $length = 3;
                break;

            case 'amex':
                $length = 4;
                break;
        }

        return $length;
    }

    /**
     * Gets whether or not this card type uses an inception date.
     *
     * Card types that conventionally use an inception date are 'solo',
     * 'switch' and 'amex'.
     *
     * @return bool true if this card type uses an inception date and
     *              false if this card type does not use an inception
     *              date
     *
     * @see StorePaymentMethod::$card_inception_date
     */
    public function hasInceptionDate()
    {
        $card_types = [
            'solo',
            'switch',
            'amex',
        ];

        return in_array($this->shortname, $card_types);
    }

    /**
     * Gets whether or not this card type uses an issue number.
     *
     * Card types that conventionally use an issue number are 'solo' and
     * 'switch'.
     *
     * @return bool true if this card type uses an issue number and false
     *              if this card type does not use an issue number
     *
     * @see StorePaymentMethod::$issue_number
     */
    public function hasIssueNumber()
    {
        $card_types = [
            'solo',
            'switch',
        ];

        return in_array($this->shortname, $card_types);
    }

    /**
     * Formats a card number according to a format string.
     *
     * Format strings may contain spaces, hash marks or stars.
     * ' ' - inserts a space at this position
     * '*' - displays a * at this position
     * '#' - displays the number at this position
     *
     * For example:
     * <code>
     * // displays '*** **6 7890'
     * echo StoreCardType::formatCardNumber(1234567890, '*** **# ####');
     * </code>
     *
     * @param string $number    the card number to format
     * @param string $format    the format string to use
     * @param bool   $zero_fill whether or not the prepend the card number
     *                          with zeros until it is as long as the format
     *                          string
     *
     * @return string the formatted card number
     */
    public static function formatCardNumber(
        $number,
        $format = '#### #### #### ####',
        $zero_fill = true
    ) {
        $number = trim((string) $number);
        $output = '';
        $format_len = mb_strlen(str_replace(' ', '', $format));

        // trim the number if it is too big
        if (mb_strlen($number) > $format_len) {
            $number = mb_substr($number, 0, $format_len);
        }

        // expand the number if it is too small
        if (mb_strlen($number) < $format_len) {
            $number = ($zero_fill) ?
                str_pad($number, $format_len, '0', STR_PAD_LEFT) :
                str_pad($number, $format_len, '*', STR_PAD_LEFT);
        }

        // format number (from right to left)
        $numberpos = mb_strlen($number) - 1;
        for ($i = mb_strlen($format) - 1; $i >= 0; $i--) {
            $char = $format[$i];
            switch ($char) {
                case '#':
                    $output = $number[$numberpos] . $output;
                    $numberpos--;
                    break;

                case '*':
                    $output = '*' . $output;
                    $numberpos--;
                    break;

                case ' ':
                    $output = ' ' . $output;
                    break;
            }
        }

        return $output;
    }

    /**
     * Looks up details about a card based on the card number.
     *
     * Information about the card type, card length, and card number prefixes
     * is returned.
     *
     * Each card type has a unique set of prefix for their card numbers. This
     * method gets card information based on the number prefix data from
     * {@link http://en.wikipedia.org/wiki/Credit_card_number}.
     *
     * @param string $number the card number for which to get card details.
     *                       Space characters in the number are ignored,
     *                       allowing formatted numbers to be checked.
     *
     * @return stdClass an object describing the card type containing the
     *                  following properties:
     *                  - (string) description
     *                  - (string) shortname
     *                  - (array)  prefixes
     *                  - (array)  length
     *                  If no suitable card information is available, null is
     *                  returned
     */
    public static function getInfoFromCardNumber($number)
    {
        static $types = null;

        if ($types === null) {
            /*
             * Note: The order of types is important. Types are checked in
             * order so a Visa Electron card with prefix 4917 will be detected
             * before a Visa card with prefix 4.
             */

            $types = [];

            $type = new stdClass();
            $type->description = Store::_('American Express');
            $type->shortname = 'amex';
            $type->prefixes = [34, 37];
            $type->length = [15];
            $types[] = $type;

            // This will not match US & Canada Diners Club cards. They will
            // match as Mastercard since they share the same prefix and length.
            $type = new stdClass();
            $type->description = Store::_('Diners Club');
            $type->shortname = 'dinersclub';
            $type->prefixes = [36]; // International only
            $type->length = [14];
            $types[] = $type;

            $type = new stdClass();
            $type->description = Store::_('Discover Card');
            $type->shortname = 'discover';
            $type->prefixes = [6011, 65];
            $type->length = [16];
            $types[] = $type;

            $type = new stdClass();
            $type->description = Store::_('China Union Pay');
            $type->shortname = 'unionpay';
            $type->prefixes = [622];
            $type->length = [16, 17, 18, 19];
            $types[] = $type;

            $type = new stdClass();
            $type->description = Store::_('JCB');
            $type->shortname = 'jcb';
            $type->prefixes = [35];
            $type->length = [16];
            $types[] = $type;

            // JCB listed twice intentionally
            $type = new stdClass();
            $type->description = Store::_('JCB');
            $type->shortname = 'jcb';
            $type->prefixes = [1800, 2131];
            $type->length = [15];
            $types[] = $type;

            $type = new stdClass();
            $type->description = Store::_('Maestro');
            $type->shortname = 'maestro';
            $type->prefixes = [5020, 5038, 6304, 6759];
            $type->length = [16, 18];
            $types[] = $type;

            $type = new stdClass();
            $type->description = Store::_('MasterCard');
            $type->shortname = 'mastercard';
            $type->prefixes = [51, 52, 53, 54, 55];
            $type->length = [16];
            $types[] = $type;

            $type = new stdClass();
            $type->description = Store::_('Solo');
            $type->shortname = 'solo';
            $type->prefixes = [6334, 6767];
            $type->length = [16, 18, 19];
            $types[] = $type;

            // Also known as Switch
            $type = new stdClass();
            $type->description = Store::_('UK Maestro');
            $type->shortname = 'switch';
            $type->prefixes = [4903, 4905, 4911, 4936,
                564182, 633110, 6333, 6759];

            $type->length = [16, 18, 19];
            $types[] = $type;

            /*
            // TODO: missing prefix data for Visa Debit
            $type = new stdClass();
            $type->description = Store::_('Visa Debit');
            $type->shortname = 'delta';
            $type->prefixes = array();
            $type->length = array(16);
            $types[] = $type;
            */

            $type = new stdClass();
            $type->description = Store::_('Visa Electron');
            $type->shortname = 'electron';
            $type->prefixes = [417500, 4917, 4913, 4508, 4844];
            $type->length = [16];
            $types[] = $type;

            $type = new stdClass();
            $type->description = Store::_('Visa');
            $type->shortname = 'visa';
            $type->prefixes = [4];
            $type->length = [13, 16];
            $types[] = $type;
        }

        $info = null;
        $number = str_replace(' ', '', $number);
        $number_length = mb_strlen($number);

        foreach ($types as $type) {
            if (!in_array($number_length, $type->length)) {
                continue;
            }

            foreach ($type->prefixes as $prefix) {
                if (strncmp($number, $prefix, mb_strlen($prefix)) == 0) {
                    $info = clone $type;
                    break 2;
                }
            }
        }

        return $info;
    }

    public static function getAcceptedCardTypesMessage(
        StoreCardTypeWrapper $types
    ) {
        $type_list = [];

        foreach ($types as $type) {
            $type_list[] = $type->title;
        }

        return sprintf(
            Store::_('We accept %s.'),
            SwatString::toList($type_list)
        );
    }

    protected function init()
    {
        $this->table = 'CardType';
        $this->id_field = 'integer:id';
    }

    /**
     * Displays this card type.
     */
    public function display()
    {
        echo SwatString::minimizeEntities($this->title);

        if (mb_strlen($this->note) > 0) {
            printf(
                '<br /><span class="swat-note">%s</span>',
                $this->note
            );
        }
    }

    // deprecated methods

    /**
     * Gets the masked format string for this card type.
     *
     * @deprecated use StoreCardType::getMaskedFormat() instead
     */
    public function getCardMaskedFormat()
    {
        return $this->getMaskedFormat();
    }

    /**
     * Gets the format string for this card type.
     *
     * @deprecated use StoreCardType::getFormat() instead
     */
    public function getCardFormat()
    {
        return $this->getFormat();
    }

    /**
     * Gets the length of the card number preview field for this card type.
     *
     * @deprecated use StoreCardType::getNumberPreviewLength() instead
     */
    public function getCardNumberPreviewLength()
    {
        return $this->getNumberPreviewLength();
    }
}
