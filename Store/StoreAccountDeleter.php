<?php

/**
 * Removes personal data from inactive accounts.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountDeleter extends SitePrivateDataDeleter
{
    // {{{ class constants

    /**
     * How many records to process in a single iteration.
     *
     * @var int
     */
    public const DATA_BATCH_SIZE = 100;

    // }}}
    // {{{ public function run()

    public function run()
    {
        $this->app->debug("\n" . Store::_('Accounts') . "\n--------\n");

        $total = $this->getTotal();
        if ($total == 0) {
            $this->app->debug(Store::_('No inactive accounts found. ' .
                'No private data removed.') . "\n");
        } else {
            $this->app->debug(sprintf(
                Store::_('Found %s inactive accounts for cleaning:') . "\n\n",
                $total
            ));

            if (!$this->app->isDryRun()) {
                /*
                 * Transactions are not used because dataobject saving already
                 * uses transactions.
                 */

                $accounts = $this->getAccounts();
                $count = count($accounts);
                while ($count > 0) {
                    foreach ($accounts as $account) {
                        $this->app->debug(
                            sprintf(
                                '=> ' . Store::_('cleaning account #%s ... '),
                                $account->id
                            )
                        );

                        $this->cleanAccount($account);
                        $account->save();
                        $this->app->debug(Store::_('done') . "\n");
                    }

                    // get next batch of accounts
                    $accounts = $this->getAccounts();
                    $count = count($accounts);
                }
            } else {
                $this->app->debug('=> ' .
                    Store::_('not cleaning because dry-run is on') . "\n");
            }

            $this->app->debug("\n" .
                Store::_('Finished cleaning inactive accounts.') . "\n");
        }
    }

    // }}}
    // {{{ protected function cleanAccount()

    /**
     * Clears an account of private data.
     *
     * @param StoreAccount $account the account to clear
     */
    protected function cleanAccount(StoreAccount $account)
    {
        $sql = sprintf(
            'delete from AccountAddress where account = %s',
            $this->app->db->quote($account->id, 'integer')
        );

        $addresses = SwatDB::exec($this->app->db, $sql);
        if ($addresses > 0) {
            $this->app->debug(sprintf(
                Store::_('%s addresses ... '),
                $addresses
            ));
        }

        $sql = sprintf(
            'delete from AccountPaymentMethod where account = %s',
            $this->app->db->quote($account->id, 'integer')
        );

        $payment_methods = SwatDB::exec($this->app->db, $sql);
        if ($payment_methods > 0) {
            $this->app->debug(sprintf(
                Store::_('%s payment methods ... '),
                $addresses
            ));
        }

        $account->fullname = null;
        $account->email = null;
        $account->phone = null;
        $account->password = null;
        $account->password_salt = null;
        $account->password_tag = null;
        $account->last_login = null;
    }

    // }}}
    // {{{ protected function getAccounts()

    protected function getAccounts()
    {
        $sql = sprintf(
            'select * from Account %s',
            $this->getWhereClause()
        );

        $this->app->db->setLimit(self::DATA_BATCH_SIZE);

        $wrapper_class = SwatDBClassMap::get('StoreAccountWrapper');

        return SwatDB::query($this->app->db, $sql, $wrapper_class);
    }

    // }}}
    // {{{ protected function getTotal()

    protected function getTotal()
    {
        $sql = sprintf(
            'select count(id) from Account %s',
            $this->getWhereClause()
        );

        return SwatDB::queryOne($this->app->db, $sql);
    }

    // }}}
    // {{{ protected function getExpiryDate()

    protected function getExpiryDate()
    {
        $unix_time = strtotime('-' . $this->app->config->expiry->accounts);

        $expiry_date = new SwatDate();
        $expiry_date->setTimestamp($unix_time);
        $expiry_date->toUTC();

        return $expiry_date;
    }

    // }}}
    // {{{ protected function getWhereClause()

    protected function getWhereClause()
    {
        $expiry_date = $this->getExpiryDate();
        $instance_id = $this->app->getInstanceId();

        $sql = 'where last_login < %s
			and fullname is not null
			and instance %s %s';

        return sprintf(
            $sql,
            $this->app->db->quote($expiry_date->getDate(), 'date'),
            SwatDB::equalityOperator($instance_id),
            $this->app->db->quote($instance_id, 'integer')
        );
    }

    // }}}
}
