<?php

/**
 * Order page for payment types.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentTypeOrder extends AdminDBOrder
{
    // process phase

    protected function saveIndex($id, $index)
    {
        SwatDB::updateColumn(
            $this->app->db,
            'PaymentType',
            'integer:displayorder',
            $index,
            'integer:id',
            [$id]
        );
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $frame = $this->ui->getWidget('order_frame');
        $frame->title = Store::_('Order Payment Types');
    }

    protected function loadData()
    {
        $order_widget = $this->ui->getWidget('order');
        $order_widget->addOptionsByArray(SwatDB::getOptionArray(
            $this->app->db,
            'PaymentType',
            'title',
            'id',
            'displayorder, title'
        ));

        $sql = 'select sum(displayorder) from PaymentType';
        $sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
        $options_list = $this->ui->getWidget('options');
        $options_list->value = ($sum == 0) ? 'auto' : 'custom';
    }
}
