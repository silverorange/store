<?php

/**
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutProgress extends SwatControl
{
    // {{{ public properties

    /**
     * @var int
     */
    public $current_step = 0;

    /**
     * @var array
     */
    public $steps = [];

    // }}}
    // {{{ public function __construct()

    public function __construct($id = null)
    {
        parent::__construct($id);

        $this->steps = [
            '1' => [
                'title' => Store::_('Your Information'),
                'link'  => null,
            ],
            '2' => [
                'title' => Store::_('Review Order'),
                'link'  => null,
            ],
            '3' => [
                'title' => Store::_('Order Completed'),
                'link'  => null,
            ],
        ];

        $this->addStyleSheet(
            'packages/store/styles/store-checkout-progress.css'
        );
    }

    // }}}
    // {{{ public function display()

    public function display()
    {
        if (!$this->visible) {
            return;
        }

        parent::display();

        $ol_tag = new SwatHtmlTag('ol');
        $ol_tag->id = $this->id;

        if ($this->current_step > 0) {
            $ol_tag->class = ' store-checkout-progress-step' .
                $this->current_step;
        }

        echo '<div class="store-checkout-progress">';
        $ol_tag->open();

        foreach ($this->steps as $id => $step) {
            $li_tag = new SwatHtmlTag('li');
            $li_tag->class = 'store-checkout-progress-step' . $id;
            $li_tag->open();

            if (isset($step['link']) && $step['link'] != '') {
                printf(
                    '<a class="store-checkout-progress-title" href="%s">' .
                    '<span class="store-checkout-progress-number">%s</span> ' .
                    '<span class="store-checkout-progress-content">%s</span>' .
                    '</a>',
                    SwatString::minimizeEntities($step['link']),
                    SwatString::minimizeEntities($id),
                    SwatString::minimizeEntities($step['title'])
                );
            } else {
                printf(
                    '<span class="store-checkout-progress-title">' .
                    '<span class="store-checkout-progress-number">%s</span> ' .
                    '<span class="store-checkout-progress-content">%s</span>' .
                    '</span>',
                    SwatString::minimizeEntities($id),
                    SwatString::minimizeEntities($step['title'])
                );
            }

            $li_tag->close();
        }

        $ol_tag->close();
        echo '<div class="store-checkout-progress-clear"></div>';
        echo '</div>';
    }

    // }}}
}
