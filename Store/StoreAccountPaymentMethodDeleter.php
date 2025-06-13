<?php

/**
 * Removes expired payment methods.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountPaymentMethodDeleter extends SitePrivateDataDeleter
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
        $this->app->debug(sprintf(
            "\n%s\n-------------------\n",
            Store::_('Credit Card Numbers')
        ));

        $total = $this->getTotal();
        if ($total == 0) {
            $this->app->debug(Store::_('No expired credit cards found. ' .
                'No private data removed.') . "\n");
        } else {
            $this->app->debug(sprintf(
                Store::_('Found %s expired credit cards for deletion:') . "\n\n",
                $total
            ));

            if (!$this->app->isDryRun()) {
                /*
                 * Transactions are not used because dataobject saving already
                 * uses transactions.
                 */

                $payment_methods = $this->getPaymentMethods();
                $count = count($payment_methods);
                while ($count > 0) {
                    foreach ($payment_methods as $payment_method) {
                        $this->app->debug(
                            sprintf(
                                '=> %s #%s ...',
                                Store::_('cleaning payment method'),
                                $payment_method->id
                            )
                        );

                        $payment_method->delete();
                        $this->app->debug(Store::_('done') . "\n");
                    }

                    // get next batch of accounts
                    $payment_methods = $this->getPaymentMethods();
                    $count = count($payment_methods);
                }
            } else {
                $this->app->debug('=> ' .
                    Store::_('not deleting because dry-run is on') . "\n");
            }

            $this->app->debug("\n" .
                Store::_('Finished deleting expired credit cards.') . "\n");
        }
    }

    // }}}
    // {{{ protected function getPaymentMethods()

    protected function getPaymentMethods()
    {
        $sql = sprintf(
            'select * from AccountPaymentMethod %s',
            $this->getWhereClause()
        );

        $this->app->db->setLimit(self::DATA_BATCH_SIZE);

        $wrapper_class =
            SwatDBClassMap::get('StoreAccountPaymentMethodWrapper');

        return SwatDB::query($this->app->db, $sql, $wrapper_class);
    }

    // }}}
    // {{{ protected function getTotal()

    protected function getTotal()
    {
        $sql = sprintf(
            'select count(id) from AccountPaymentMethod %s',
            $this->getWhereClause()
        );

        return SwatDB::queryOne($this->app->db, $sql);
    }

    // }}}
    // {{{ protected function getExpiryDate()

    protected function getExpiryDate()
    {
        // credit cards expire now
        return new SwatDate();
    }

    // }}}
    // {{{ protected function getWhereClause()

    protected function getWhereClause()
    {
        $expiry_date = $this->getExpiryDate();
        $instance_id = $this->app->getInstanceId();

        $sql = 'where card_expiry < %s
			and account in (select id from Account where instance %s %s)';

        return sprintf(
            $sql,
            $this->app->db->quote($expiry_date->getDate(), 'date'),
            SwatDB::equalityOperator($instance_id),
            $this->app->db->quote($instance_id, 'integer')
        );
    }

    // }}}
}
