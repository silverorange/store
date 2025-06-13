<?php

/**
 * Remove products confirmation page for Categories.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryRemoveProducts extends AdminDBConfirmation
{
    // {{{ private properties

    private $category_id;

    // }}}
    // {{{ public function setCategory()

    public function setCategory($category_id)
    {
        $this->category_id = $category_id;
    }

    // }}}

    // init phase
    // {{{ protected function initInternal()

    protected function initInternal()
    {
        parent::initInternal();
        $this->category_id = SiteApplication::initVar('category');
    }

    // }}}

    // process phase
    // {{{ protected function processDBData()

    protected function processDBData(): void
    {
        parent::processDBData();

        $sql = 'select count(distinct category) from CategoryProductBinding
			where category in (%s)';
        $item_list = $this->getItemList('integer');
        $sql = sprintf($sql, $item_list);
        $num = SwatDB::queryOne($this->app->db, $sql);

        $sql = 'delete from CategoryProductBinding where category in (%s)';
        $item_list = $this->getItemList('integer');
        $sql = sprintf($sql, $item_list);

        SwatDB::query($this->app->db, $sql);

        $message = new SwatMessage(sprintf(
            Store::ngettext(
                'One category has had its products removed.',
                '%s categories have had their products removed.',
                $num
            ),
            SwatString::numberFormat($num)
        ), 'notice');

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // }}}

    // build phase
    // {{{ protected function buildInternal()

    protected function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('integer');

        $sql = sprintf('select Category.id, max(Category.title) as title,
				count(CategoryProductBinding.product) as num_products
			from Category
			left outer join CategoryProductBinding on
				Category.id = CategoryProductBinding.category
			where Category.id in (%s)
			group by Category.id
			order by max(Category.title)', $item_list);

        $rs = SwatDB::query($this->app->db, $sql);

        $valid_rows = [];
        $invalid_rows = [];

        foreach ($rs as $row) {
            if ($row->num_products == 0) {
                $invalid_rows[] = $row;
            } else {
                $valid_rows[] = $row;
            }
        }

        $message_text = '';
        if (count($valid_rows) > 0) {
            $message_text .= sprintf(
                '<h3>%s</h3><ul>',
                Store::_('Remove all products from the following categories?')
            );

            foreach ($valid_rows as $row) {
                $message_text .= sprintf(
                    Store::_('<li>%s - %s product(s)</li>'),
                    $row->title,
                    $row->num_products
                );
            }

            $message_text .= '</ul>';
            $this->ui->getWidget('yes_button')->title = Store::_('Remove');
        } else {
            $this->switchToCancelButton();
        }

        if (count($invalid_rows) > 0) {
            $message_text .= sprintf(
                '<p><strong>%s</strong></p><ul>',
                Store::_('There are no products attached to the following ' .
                'categories:')
            );

            foreach ($invalid_rows as $row) {
                $message_text .= '<li>' . $row->title . '</li>';
            }

            $message_text .= '</ul>';
        }

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $message_text;
        $message->content_type = 'text/xml';

        $note = $this->ui->getWidget('note');
        $note->visible = true;
        $note->content_type = 'text/xml';
        $note->content = Store::_('Removed products <em>will not be ' .
            'deleted</em>. Removed products will only be removed from the ' .
            'categories above.');

        $form = $this->ui->getWidget('confirmation_form');
        $form->addHiddenField('category', $this->category_id);
    }

    // }}}
    // {{{ protected function buildNavBar()

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $this->navbar->popEntry();

        if ($this->category_id !== null) {
            $navbar_rs = SwatDB::executeStoredProc(
                $this->app->db,
                'getCategoryNavbar',
                [$this->category_id]
            );

            foreach ($navbar_rs as $row) {
                $this->navbar->addEntry(new SwatNavBarEntry(
                    $row->title,
                    'Category/Index?id=' . $row->id
                ));
            }
        }

        $this->navbar->addEntry(new SwatNavBarEntry(
            Store::_('Remove Products')
        ));
    }

    // }}}
}
