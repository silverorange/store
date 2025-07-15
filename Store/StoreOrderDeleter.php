<?php

/**
 * Removes personal data from expired orders.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderDeleter extends SitePrivateDataDeleter
{
    /**
     * How many records to process in a single iteration.
     *
     * @var int
     */
    public const DATA_BATCH_SIZE = 100;

    public function run()
    {
        $this->app->debug("\n" . Store::_('Orders') . "\n------\n");

        $total = $this->getTotal();
        if ($total == 0) {
            $this->app->debug(Store::_('No expired orders found. ' .
                'No private data removed.') . "\n");
        } else {
            $this->app->debug(
                sprintf(
                    Store::_('Found %s expired orders for deletion:') . "\n",
                    $total
                )
            );

            if (!$this->app->isDryRun()) {
                /*
                 * Transactions are not used because dataobject saving already
                 * uses transactions.
                 */

                $orders = $this->getOrders();
                $count = count($orders);
                while ($count > 0) {
                    foreach ($orders as $order) {
                        $this->app->debug(sprintf(
                            '=> ' . Store::_('cleaning order %s ... '),
                            $order->id
                        ));

                        $this->cleanOrder($order);
                        $order->save();
                        $this->app->debug("done\n");
                    }

                    // get next batch of orders
                    $orders = $this->getOrders();
                    $count = count($orders);
                }
            } else {
                $this->app->debug('=> ' .
                    Store::_('not cleaning because dry-run is on') . "\n");
            }

            $this->app->debug(
                Store::_('Finished cleaning expired orders.') . "\n"
            );
        }
    }

    /**
     * Clears an order of private data.
     *
     * @param StoreOrder $order the order to clear
     */
    protected function cleanOrder(StoreOrder $order)
    {
        $order->email = null;
        $order->phone = null;
        $order->comments = null;
        $order->admin_comments = null;
        $order->notes = null;

        if (count($order->payment_methods)) {
            foreach ($order->payment_methods as $payment_method) {
                $payment_method->card_fullname = null;
                $payment_method->card_number_preview = null;
                $payment_method->card_number = null;
                $payment_method->card_expiry = null;
            }
        }

        $order->billing_address->fullname = '';
        $order->billing_address->company = '';
        $order->billing_address->phone = '';
        $order->billing_address->line1 = '';
        $order->billing_address->line2 = null;
        $order->billing_address->city = '';
        $order->billing_address->postal_code =
            $this->cleanPostalCode($order->billing_address);

        if ($order->shipping_address instanceof StoreOrderAddress) {
            $order->shipping_address->fullname = '';
            $order->shipping_address->company = '';
            $order->shipping_address->phone = '';
            $order->shipping_address->line1 = '';
            $order->shipping_address->line2 = null;
            $order->shipping_address->city = '';
            $order->shipping_address->postal_code = $this->cleanPostalCode(
                $order->shipping_address
            );
        }
    }

    /**
     * Removes personally identifiable information from postal codes and Zip
     * Codes.
     *
     * @param StoreAddress $address the address which contains the postal code
     *                              to clean
     *
     * @return string the postal code with personally identifiable information
     *                removed
     */
    protected function cleanPostalCode(StoreAddress $address)
    {
        $postal_code = '';
        switch ($address->getInternalValue('country')) {
            case 'CA':
                // leave off local delivery unit from postal code
                $postal_code = mb_substr($address->postal_code, 0, 3);
                break;

            case 'US':
                // leave off +4 from Zip Code
                $postal_code = mb_substr($address->postal_code, 0, 5);
                break;
        }

        return $postal_code;
    }

    protected function getOrders()
    {
        // join billing address for where clause
        $sql = 'select Orders.* from Orders
			inner join OrderAddress on Orders.billing_address = OrderAddress.id
			%s';

        $sql = sprintf(
            $sql,
            $this->getWhereClause()
        );

        $this->app->db->setLimit(self::DATA_BATCH_SIZE);

        $wrapper_class = SwatDBClassMap::get(StoreOrderWrapper::class);

        return SwatDB::query($this->app->db, $sql, $wrapper_class);
    }

    protected function getTotal()
    {
        $sql = 'select count(Orders.id) from Orders
			inner join OrderAddress on Orders.billing_address = OrderAddress.id
			%s';

        $sql = sprintf(
            $sql,
            $this->getWhereClause()
        );

        return SwatDB::queryOne($this->app->db, $sql);
    }

    protected function getExpiryDate()
    {
        $unix_time = strtotime('-' . $this->app->config->expiry->orders);

        $expiry_date = new SwatDate();
        $expiry_date->setTimestamp($unix_time);
        $expiry_date->toUTC();

        return $expiry_date;
    }

    protected function getWhereClause()
    {
        $expiry_date = $this->getExpiryDate();
        $instance_id = $this->app->getInstanceId();

        $sql = 'where length(OrderAddress.fullname) > 0
			and createdate < %s
			and instance %s %s';

        return sprintf(
            $sql,
            $this->app->db->quote($expiry_date->getDate(), 'date'),
            SwatDB::equalityOperator($instance_id),
            $this->app->db->quote($instance_id, 'integer')
        );
    }
}
