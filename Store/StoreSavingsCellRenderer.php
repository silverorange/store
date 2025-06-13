<?php

/**
 * A percentage cell renderer for savings.
 *
 * @copyright 2007-2016 silverorange
 */
class StoreSavingsCellRenderer extends SwatPercentageCellRenderer
{
    // {{{ public function __construct()

    public function __construct()
    {
        parent::__construct();

        $this->precision = 0;
        $this->classes[] = 'store-savings-cell-renderer';
    }

    // }}}
    // {{{ public function render()

    /**
     * Renders the contents of this cell.
     *
     * @see SwatCellRenderer::render()
     */
    public function render()
    {
        if (!$this->visible) {
            return;
        }

        if ($this->value <= 0) {
            return;
        }

        $tag = new SwatHtmlTag('span');
        $tag->class = $this->getCSSClassString();

        ob_start();
        parent::render();
        $value = ob_get_clean();

        $tag->open();
        printf('Save %s', $value);
        $tag->close();
    }

    // }}}
}
