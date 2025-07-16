<?php

/**
 * A custom action for grouping items inside products.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroupAction extends SwatControl
{
    public $db;
    public $product_id;

    private $groups;
    private $group_title;
    private $options;

    public function __construct($id = null)
    {
        parent::__construct($id);

        $yui = new SwatYUI(['event']);
        $this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

        $this->addJavaScript(
            'packages/store/admin/javascript/store-item-group-action.js'
        );
    }

    public function init()
    {
        parent::init();

        $this->groups = new SwatFlydown($this->id . '_groups');
        $this->groups->show_blank = false;
        $this->groups->parent = $this;

        $this->group_title = new SwatEntry($this->id . '_title');
        $this->group_title->value = Store::_('group name');
        $this->group_title->size = 10;
        $this->group_title->parent = $this;

        $this->options = SwatDB::getOptionArray(
            $this->db,
            'ItemGroup',
            'title',
            'id',
            'title',
            sprintf('product = %s', $this->product_id)
        );
    }

    public function display()
    {
        parent::display();

        if (count($this->options)) {
            $this->groups->addOptionsByArray($this->options);
            $this->groups->addDivider();
        }

        $this->groups->addOption('no_group', Store::_('<none>'));
        $this->groups->addOption('new_group', Store::_('<new group>'));

        $this->groups->display();
        $this->group_title->display();
        Swat::displayInlineJavaScript($this->getInlineJavaScript());
    }

    public function process()
    {
        $this->groups->process();
        $this->group_title->process();
    }

    public function processAction($items)
    {
        $message = null;
        $group_id = $this->groups->value;

        // create a new item group
        if ($group_id === 'new_group') {
            $new_title = $this->group_title->value;

            $group_id = SwatDB::insertRow(
                $this->db,
                'ItemGroup',
                ['title', 'integer:product'],
                ['title' => $new_title, 'product' => $this->product_id],
                'id'
            );

            $message = new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been added to the new group “%2$s”.',
                        '%s items have been added to the new group “%s”.',
                        count($items)
                    ),
                    SwatString::numberFormat(count($items)),
                    $new_title
                ),
                'notice'
            );
        } elseif ($group_id === 'no_group') {
            $group_id = null;

            $message = new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been removed from a group.',
                        '%s items have been removed from group(s).',
                        count($items)
                    ),
                    SwatString::numberFormat(count($items))
                ),
                'notice'
            );
        } else {
            $sql = 'select title from ItemGroup where id = %s';
            $sql = sprintf($sql, $this->db->quote($group_id, 'integer'));
            $old_title = SwatDB::queryOne($this->db, $sql);
            $message = new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been added to the group “%2$s”.',
                        '%s items have been added to the group “%s”.',
                        count($items)
                    ),
                    SwatString::numberFormat(count($items)),
                    $old_title
                ),
                'notice'
            );
        }

        SwatDB::updateColumn(
            $this->db,
            'Item',
            'integer:item_group',
            $group_id,
            'integer:id',
            $items
        );

        return $message;
    }

    public function getFocusableHtmlId()
    {
        if ($this->groups === null) {
            return null;
        }

        return $this->groups->id;
    }

    protected function getInlineJavaScript()
    {
        $values = [];
        foreach ($this->groups->options as $option) {
            $values[] = "'" . $option->value . "'";
        }

        return sprintf(
            "var %s = new ItemGroupAction('%s', [%s]);\n",
            $this->id,
            $this->id,
            implode(', ', $values)
        );
    }
}
