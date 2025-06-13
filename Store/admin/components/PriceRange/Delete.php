<?php

/**
 * Delete confirmation page for PriceRanges.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceRangeDelete extends AdminDBDelete
{
    // process phase
    // {{{ protected funtion processDBData()

    protected function processDBData(): void
    {
        parent::processDBData();

        $sql = $this->getProcessSQL();
        $item_list = $this->getItemList('integer');
        $sql = sprintf($sql, $item_list);
        $num = SwatDB::exec($this->app->db, $sql);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('price_ranges');
        }

        $message = new SwatMessage(
            sprintf(
                Store::ngettext(
                    'One price range has been deleted.',
                    '%s price ranges have been deleted.',
                    $num
                ),
                SwatString::numberFormat($num)
            ),
            'notice'
        );

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // }}}
    // {{{ protected function getProcessSQL()

    protected function getProcessSQL()
    {
        return 'delete from PriceRange where id in (%s)';
    }

    // }}}

    // build phase
    // {{{ protected function buildInternal()

    protected function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('integer');

        $count = $this->getItemCount();

        $content = sprintf(
            Store::ngettext(
                'Delete one price range?',
                'Delete %s price ranges?',
                $count
            ),
            SwatString::numberFormat($count)
        );

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = '<h3>' . $content . '</h3>';
        $message->content_type = 'text/xml';
    }

    // }}}
}
