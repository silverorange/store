<?php

/**
 * A account for an e-commerce web application.
 *
 * StoreAccount objects contain data like name and email that correspond
 * directly to database fields. StoreAccount objects may have one or more
 * StoreAccountAddress objects, one or more StoreAccountPaymentMethod objects
 * and one or more StoreOrder objects all accessed as sub-data-objects.
 *
 * There are three typical ways to use a StoreAccount object:
 *
 * - Create a new StoreAccount object with a blank constructor. Modify some
 *   properties of the account object and call the StoreAccount::save()
 *   method. A new row is inserted into the database.
 *
 * <code>
 * $new_account = new StoreAccount();
 * $new_account->setEmail('account@example.com');
 * $new_account->setFullname('Example Customer');
 * $new_account->save();
 * </code>
 *
 * Using this technique, you may also add addresses and payment methods as sub-
 * data-objects and have them save automatically when you call
 * {@link StoreAccount::save()}.
 *
 * - Create a new StoreAccount object with a blank constructor. Call the
 *   {@link StoreAccount::load()} or {@link StoreAccount::loadWithEmail}
 *   method on the object instance. Modify some properties and call the save()
 *   method. The modified properties are updated in the database.
 *
 * <code>
 * // using regular data-object load() method
 * $account = new StoreAccount();
 * $account->load(123);
 * echo 'Hello ' . $account->getFullName();
 * $account->setEmail('new_address@example.com');
 * $account->save();
 *
 * // using loadWithEmail()
 * $account = new StoreAccount();
 * if ($account->loadWithEmail('test@example.com')) {
 *     $password = 'secretpassword';
 *     $password_hash = $account->password;
 *     $password_salt = $account->password_salt;
 *
 *     $crypt = $this->app->getModule('SiteCryptModule');
 *
 *     if ($crypt->verifyHash($password, $password_hash, $password_salt)) {
 *         echo 'Hello ' . $account->getFullName();
 *         $account->setEmail('new_address@example.com');
 *         $account->save();
 *     }
 * }
 * </code>
 *
 * - Create a new StoreAccount object passing a record set into the
 *   constructor. The first row of the record set will be loaded as the data
 *   for the object instance. Modify some properties and call the
 *   StoreAccount::save() method. The modified properties are updated
 *   in the database.
 *
 * Example usage as an MDB2 wrapper:
 *
 * <code>
 * $sql = '-- select a account here';
 * $account = $db->query($sql, null, true, 'Account');
 * echo 'Hello ' . $account->getFullName();
 * $account->setEmail('new_address@example.com');
 * $account->save();
 * </code>
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreAccountWrapper
 */
class StoreAccount extends SiteAccount
{
    /**
     * @var float
     */
    public $available_credit;

    /**
     * Optional company name for this account.
     *
     * @var string
     */
    protected $company;

    /**
     * Phone number of this account.
     *
     * @var string
     */
    protected $phone;

    public function setDefaultBillingAddress(StoreAccountAddress $address)
    {
        if ($address->getId() === null) {
            $this->addresses->add($address);
        } else {
            $actual_address = $this->addresses->getByIndex($address->getId());
            if ($actual_address === null) {
                throw new SwatObjectNotFoundException(
                    'Address does not belong to this account and cannot be ' .
                    'set as the default address.'
                );
            }
        }

        $this->setSubDataObject('default_billing_address', $address);
        $this->setInternalValue('default_billing_address', $address->getId());
    }

    public function getDefaultBillingAddress()
    {
        $address = null;

        if ($this->hasSubDataObject('default_billing_address')) {
            $address = $this->getSubDataObject('default_billing_address');
        } else {
            $id = $this->getInternalValue('default_billing_address');
            if ($id !== null) {
                $address = $this->addresses->getByIndex($id);
            }
        }

        return $address;
    }

    public function setDefaultShippingAddress(StoreAccountAddress $address)
    {
        if ($address->getId() === null) {
            $this->addresses->add($address);
        } else {
            $actual_address = $this->addresses->getByIndex($address->getId());
            if ($actual_address === null) {
                throw new SwatObjectNotFoundException(
                    'Address does not belong to this account and cannot be ' .
                    'set as the default address.'
                );
            }
        }

        $this->setSubDataObject('default_shipping_address', $address);
        $this->setInternalValue('default_shipping_address', $address->getId());
    }

    public function getDefaultShippingAddress()
    {
        $address = null;

        if ($this->hasSubDataObject('default_shipping_address')) {
            $address = $this->getSubDataObject('default_shipping_address');
        } else {
            $id = $this->getInternalValue('default_shipping_address');
            if ($id !== null) {
                $address = $this->addresses->getByIndex($id);
            }
        }

        return $address;
    }

    public function setDefaultPaymentMethod(
        StoreAccountPaymentMethod $payment_method
    ) {
        if ($payment_method->getId() === null) {
            $this->payment_methods->add($payment_method);
        } else {
            $actual_payment_method = $this->payment_methods->getByIndex(
                $payment_method->getId()
            );

            if ($actual_payment_method === null) {
                throw new SwatObjectNotFoundException(
                    'Payment method does not belong to this account and ' .
                    'cannot be set as the default payment method.'
                );
            }
        }

        $this->setSubDataObject('default_payment_method', $payment_method);
        $this->setInternalValue(
            'default_payment_method',
            $payment_method->getId()
        );
    }

    public function getDefaultPaymentMethod()
    {
        $payment_method = null;

        if ($this->hasSubDataObject('default_payment_method')) {
            $payment_method = $this->getSubDataObject('default_payment_method');
        } else {
            $id = $this->getInternalValue('default_payment_method');
            if ($id !== null) {
                $payment_method = $this->payment_methods->getByIndex($id);
            }
        }

        return $payment_method;
    }

    public function canPayOnAccount()
    {
        return $this->available_credit > 0;
    }

    protected function init()
    {
        parent::init();

        $this->registerInternalProperty(
            'default_billing_address',
            SwatDBClassMap::get(StoreAccountAddress::class),
            false,
            false
        );

        $this->registerInternalProperty(
            'default_shipping_address',
            SwatDBClassMap::get(StoreAccountAddress::class),
            false,
            false
        );

        $this->registerInternalProperty(
            'default_payment_method',
            SwatDBClassMap::get(StoreAccountPaymentMethod::class),
            false,
            false
        );
    }

    protected function getProtectedPropertyList()
    {
        $properties = parent::getProtectedPropertyList();

        $properties['company'] = [
            'get' => 'getCompany',
            'set' => 'setCompany',
        ];

        $properties['phone'] = [
            'get' => 'getPhone',
            'set' => 'setPhone',
        ];

        return $properties;
    }

    // getters

    public function getCompany()
    {
        return $this->company;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    // setters

    public function setCompany($company)
    {
        $this->company = $company;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    // loader methods

    /**
     * Loads StoreAccountAddress sub-data-objects for this StoreAccount.
     */
    protected function loadAddresses()
    {
        $sql = sprintf(
            'select * from AccountAddress
			where account = %s
			order by id asc',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreAccountAddressWrapper::class)
        );
    }

    /**
     * Loads StoreAccountPaymentMethod sub-data-objects for this StoreAccount.
     */
    protected function loadPaymentMethods()
    {
        $sql = sprintf(
            'select * from AccountPaymentMethod
			where account = %s
			order by id asc',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreAccountPaymentMethodWrapper::class)
        );
    }

    /**
     * Loads StoreOrder sub-data-objects for this StoreAccount.
     *
     * This represents a set of past orders made with this account.
     */
    protected function loadOrders()
    {
        $sql = sprintf(
            'select * from Orders
			where account = %s
			order by id desc',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreOrderWrapper::class)
        );
    }

    // saver methods

    /**
     * Automatically saves StoreAccontAddress sub-data-objects when this
     * StoreAccount object is saved.
     */
    protected function saveAddresses()
    {
        foreach ($this->addresses as $address) {
            $address->account = $this;
        }

        $this->addresses->setDatabase($this->db);
        $this->addresses->save();
    }

    /**
     * Automatically saves StoreAccontPaymentMethod sub-data-objects when this
     * StoreAccount object is saved.
     */
    protected function savePaymentMethods()
    {
        foreach ($this->payment_methods as $payment_method) {
            $payment_method->account = $this;
        }

        $this->payment_methods->setDatabase($this->db);
        $this->payment_methods->save();
    }
}
