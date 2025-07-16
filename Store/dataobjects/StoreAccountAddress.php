<?php

/**
 * An address belonging to an account for an e-commerce web application.
 *
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreAddress
 *
 * @property StoreAccount $account
 * @property ?SwatDate    $createdate
 */
class StoreAccountAddress extends StoreAddress
{
    /**
     * Creation date.
     *
     * @var SwatDate
     */
    protected $createdate;

    protected function init()
    {
        parent::init();

        $this->table = 'AccountAddress';

        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(StoreAccount::class)
        );

        $this->registerDateProperty('createdate');
    }

    protected function getProtectedPropertyList()
    {
        return array_merge(
            parent::getProtectedPropertyList(),
            [
                'createdate' => [
                    'get' => 'getCreateDate',
                    'set' => 'setCreateDate',
                ],
            ]
        );
    }

    // getters

    public function getCreateDate()
    {
        return $this->createdate;
    }

    // setters

    public function setCreateDate(SwatDate $createdate)
    {
        $this->createdate = $createdate;
    }
}
